"""Pruebas de log y consola del agente, sin conexiones de red."""

import io
import logging
import sys
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import agent  # noqa: E402


class AgentModesTests(unittest.TestCase):
    def setUp(self):
        self.temp_dir = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp_dir.cleanup)
        self.log_path = Path(self.temp_dir.name) / "agent.log"
        self.output = io.StringIO()
        self.settings = patch.object(agent, "LOG_FILE", str(self.log_path))
        self.settings.start()
        self.addCleanup(self.settings.stop)

    def tearDown(self):
        logger = logging.getLogger("infrawatch-agent")
        for handler in logger.handlers[:]:
            logger.removeHandler(handler)
            handler.close()

    def test_silent_writes_to_file_without_console_output(self):
        with patch.object(agent.sys, "stdout", self.output):
            logger = agent.setup_logger(silent=True)
            logger.info("mensaje en archivo")
            for handler in logger.handlers:
                handler.flush()

        self.assertEqual(self.output.getvalue(), "")
        self.assertIn("mensaje en archivo", self.log_path.read_text(encoding="utf-8"))
        self.assertEqual(len(logger.handlers), 1)

    def test_verbose_enables_debug_in_console_and_file(self):
        with patch.object(agent.sys, "stdout", self.output):
            logger = agent.setup_logger(verbose=True)
            logger.debug("depuracion visible")
            for handler in logger.handlers:
                handler.flush()

        self.assertIn("depuracion visible", self.output.getvalue())
        self.assertIn("depuracion visible", self.log_path.read_text(encoding="utf-8"))
        self.assertEqual(logger.level, logging.DEBUG)

    def test_default_mode_suppresses_debug_but_emits_info(self):
        with patch.object(agent.sys, "stdout", self.output):
            logger = agent.setup_logger()
            logger.debug("oculto")
            logger.info("informativo")
            for handler in logger.handlers:
                handler.flush()

        self.assertNotIn("oculto", self.output.getvalue())
        self.assertIn("informativo", self.output.getvalue())
        self.assertNotIn("oculto", self.log_path.read_text(encoding="utf-8"))


if __name__ == "__main__":
    unittest.main()
