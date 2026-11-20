"""Pruebas unitarias del agente InfraWatch sin llamadas HTTP reales."""

import logging
import sys
import tempfile
import unittest
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import agent  # noqa: E402


class AgentTests(unittest.TestCase):
    def setUp(self):
        agent._stop_requested = False
        self.logger = logging.getLogger("infrawatch-agent-tests")
        self.logger.handlers = [logging.NullHandler()]
        self.payload = {
            "reading_id": "550e8400-e29b-41d4-a716-446655440000",
            "hostname": "test-host",
            "ip_address": "127.0.0.1",
            "cpu_usage": 15,
            "ram_usage": 30,
            "disk_usage": 20,
        }

    def tearDown(self):
        agent._stop_requested = False

    @staticmethod
    def response(status, retry_after=None):
        headers = {} if retry_after is None else {"Retry-After": retry_after}
        return SimpleNamespace(status_code=status, headers=headers)

    def test_default_execution_is_one_shot(self):
        self.assertIsNone(agent.parse_args([]).interval)
        self.assertTrue(agent.parse_args(["--once"]).once)

    def test_interval_uses_env_default_if_no_number_given(self):
        with patch.object(agent, "DEFAULT_INTERVAL_SECONDS", 73):
            self.assertEqual(agent.parse_args(["--interval"]).interval, 73)

    def test_explicit_interval_overrides_env_default(self):
        self.assertEqual(agent.parse_args(["--interval", "120"]).interval, 120)

    def test_zero_interval_is_rejected(self):
        with patch("sys.stderr"):
            with self.assertRaises(SystemExit) as error:
                agent.parse_args(["--interval", "0"])
        self.assertEqual(error.exception.code, 2)

    def test_no_retry_for_any_permanent_http_code(self):
        for status in (301, 400, 401, 403, 404, 409, 422):
            with self.subTest(status=status):
                with patch.object(agent, "API_URL", "https://example.test/api"), \
                     patch.object(agent, "AGENT_TOKEN", "sample-token"), \
                     patch.object(agent, "collect_metrics", return_value=self.payload), \
                     patch.object(agent.requests, "post", return_value=self.response(status)) as post, \
                     patch.object(agent, "sleep_interruptibly") as sleeper:
                    self.assertFalse(agent.send_metrics(self.logger))
                    post.assert_called_once()
                    sleeper.assert_not_called()

    def test_429_retries_using_retry_after_header(self):
        with patch.object(agent, "API_URL", "https://example.test/api"), \
             patch.object(agent, "AGENT_TOKEN", "sample-token"), \
             patch.object(agent, "collect_metrics", return_value=self.payload), \
             patch.object(agent.requests, "post", side_effect=[self.response(429, "12"), self.response(201)]) as post, \
             patch.object(agent, "sleep_interruptibly", return_value=True) as sleeper:
            self.assertTrue(agent.send_metrics(self.logger))
            self.assertEqual(post.call_count, 2)
            sleeper.assert_called_once_with(12.0)
            self.assertIs(post.call_args_list[0].kwargs["json"], post.call_args_list[1].kwargs["json"])

    def test_5xx_retries_and_accepts_second_response(self):
        with patch.object(agent, "API_URL", "https://example.test/api"), \
             patch.object(agent, "AGENT_TOKEN", "sample-token"), \
             patch.object(agent, "collect_metrics", return_value=self.payload), \
             patch.object(agent.requests, "post", side_effect=[self.response(503), self.response(200)]) as post, \
             patch.object(agent, "sleep_interruptibly", return_value=True) as sleeper:
            self.assertTrue(agent.send_metrics(self.logger))
            self.assertEqual(post.call_count, 2)
            sleeper.assert_called_once_with(agent.RETRY_DELAY_SECONDS)

    def test_network_error_retries_with_same_reading_id(self):
        with patch.object(agent, "API_URL", "https://example.test/api"), \
             patch.object(agent, "AGENT_TOKEN", "sample-token"), \
             patch.object(agent, "collect_metrics", return_value=self.payload) as collect, \
             patch.object(agent.requests, "post", side_effect=[agent.requests.exceptions.ConnectionError(), self.response(201)]) as post, \
             patch.object(agent, "sleep_interruptibly", return_value=True):
            self.assertTrue(agent.send_metrics(self.logger))
            collect.assert_called_once()
            self.assertEqual(post.call_count, 2)
            self.assertEqual(post.call_args_list[0].kwargs["json"]["reading_id"], self.payload["reading_id"])
            self.assertIs(post.call_args_list[0].kwargs["json"], post.call_args_list[1].kwargs["json"])

    def test_tls_certificate_error_is_not_retried(self):
        with patch.object(agent, "API_URL", "https://example.test/api"), \
             patch.object(agent, "AGENT_TOKEN", "sample-token"), \
             patch.object(agent, "collect_metrics", return_value=self.payload), \
             patch.object(agent.requests, "post", side_effect=agent.requests.exceptions.SSLError()) as post, \
             patch.object(agent, "sleep_interruptibly") as sleeper:
            self.assertFalse(agent.send_metrics(self.logger))
            post.assert_called_once()
            sleeper.assert_not_called()

    def test_signal_stops_before_next_retry(self):
        def interrupt_wait(seconds):
            agent.request_stop(15, None)
            return False
        with patch.object(agent, "API_URL", "https://example.test/api"), \
             patch.object(agent, "AGENT_TOKEN", "sample-token"), \
             patch.object(agent, "collect_metrics", return_value=self.payload), \
             patch.object(agent.requests, "post", return_value=self.response(503)) as post, \
             patch.object(agent, "sleep_interruptibly", side_effect=interrupt_wait):
            self.assertFalse(agent.send_metrics(self.logger))
            post.assert_called_once()

    def test_sigterm_stops_continuous_cycle_cleanly(self):
        def send_one_then_stop(logger):
            agent.request_stop(15, None)
            return True
        with patch.object(agent, "send_metrics", side_effect=send_one_then_stop) as send, \
             patch.object(agent, "sleep_interruptibly") as sleeper:
            self.assertEqual(agent.run_forever(60, self.logger), 0)
            send.assert_called_once()
            sleeper.assert_not_called()

    def test_log_rotation_respects_size_and_backups(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "logs" / "agent.log"
            with patch.object(agent, "LOG_FILE", str(path)), \
                 patch.object(agent, "LOG_MAX_BYTES", 220), \
                 patch.object(agent, "LOG_BACKUP_COUNT", 2):
                logger = agent.setup_logger(silent=True)
                try:
                    for index in range(12):
                        logger.info("Prueba %d: %s", index, "x" * 85)
                    self.assertTrue(path.is_file())
                    self.assertTrue(Path(f"{path}.1").is_file())
                    self.assertFalse(Path(f"{path}.3").exists())
                finally:
                    for handler in logger.handlers[:]:
                        logger.removeHandler(handler)
                        handler.close()


if __name__ == "__main__":
    unittest.main()
