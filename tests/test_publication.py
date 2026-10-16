"""Prüft Wiki-Umwandlung und Schutzregeln ohne Netzwerk oder echte Git-Ziele."""

import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
from publication import check_files, wiki_pages, write_managed  # noqa: E402


class PublicationTests(unittest.TestCase):
    """Deckt Veröffentlichungspfade und getrennte Inhalte ab."""

    def test_tokens_are_blocked_without_disclosure(self):
        """Verhindert Tokenveröffentlichung und Tokenwerte in Fehlermeldungen."""
        value = b"sk-" + b"x" * 30
        with self.assertRaises(ValueError) as caught:
            check_files({"settings.php": value})
        self.assertNotIn(value.decode(), str(caught.exception))

    def test_local_secret_files_and_rules_are_blocked(self):
        """Blockiert geschützte Dateien und lokale Betreiber-Ausschlüsse."""
        check_files({"js/vendor/lottie-player.js": b"bundled library"})
        with self.assertRaises(ValueError):
            check_files({"vendor/autoload.php": b"local dependencies"})
        with self.assertRaises(ValueError):
            check_files({".env.local": b"anything"})
        with self.assertRaises(ValueError):
            check_files(
                {"README.md": b"internal.example.invalid"}, [r"internal\.example"]
            )

    def test_wiki_links_and_images_follow_docs(self):
        """Übersetzt Home, Navigation und Bilder aus einer einzigen Dokumentquelle."""
        files = {
            "docs/index.md": (
                b"# Home\n[Help](help.md#topic)\n" b"![Image](images/test.png)"
            ),
            "docs/help.md": b"# Help\n[Home](index.md)\n## Topic\nText.",
            "docs/images/test.png": b"image-fixture",
            "mkdocs.yml": b"nav:\n  - Start: index.md\n  - Help: help.md\n",
        }
        result = wiki_pages(files, "https://github.com/example/project.git")
        self.assertIn(b"/wiki/help#topic", result["Home.md"])
        self.assertIn(
            b"raw.githubusercontent.com/wiki/example/project/images/test.png",
            result["Home.md"],
        )
        self.assertIn(b"/wiki/Home", result["help.md"])
        self.assertIn(b"[Help]", result["_Sidebar.md"])
        self.assertEqual(result["images/test.png"], b"image-fixture")

    def test_missing_links_are_rejected(self):
        """Verhindert ein Wiki mit nicht vorhandenen lokalen Linkzielen."""
        with self.assertRaises(ValueError):
            wiki_pages(
                {"docs/index.md": b"[Missing](missing.md)"},
                "https://github.com/example/project.git",
            )

    def test_managed_write_preserves_other_files(self):
        """Entfernt nur alte verwaltete Dateien und verhindert Pfadausbrüche."""
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / "manual.md").write_text("keep")
            (root / "old.md").write_text("old")
            write_managed(root, {"Home.md": b"new"}, ["old.md"])
            self.assertEqual((root / "manual.md").read_text(), "keep")
            self.assertFalse((root / "old.md").exists())
            with self.assertRaises(ValueError):
                write_managed(root, {"../outside.md": b"invalid"}, [])

    def test_full_publish_is_idempotent_and_blocks_secrets(self):
        """Prüft lokale Git-Ziele, getrennte Historien und Abbruch vor Push."""
        scripts = Path(__file__).resolve().parents[1] / "scripts"
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)

            def git(path, *args):
                """Nutzt ausschließlich temporäre Git-Repositories."""
                return (
                    subprocess.check_output(
                        ["git", "-C", str(path), *args], stderr=subprocess.DEVNULL
                    )
                    .decode()
                    .strip()
                )

            checkouts = {}
            for name in ("source", "public", "wiki"):
                bare = root / (name + ".git")
                subprocess.run(
                    ["git", "init", "--bare", "--initial-branch=main", str(bare)],
                    check=True,
                    capture_output=True,
                )
                path = root / name
                subprocess.run(
                    ["git", "clone", str(bare), str(path)],
                    check=True,
                    capture_output=True,
                )
                git(path, "config", "user.name", "Publication Test")
                git(path, "config", "user.email", "test@example.invalid")
                (path / "README.md").write_text("Initial test repository.")
                git(path, "add", ".")
                git(path, "commit", "-m", "Initial fixture")
                git(path, "push", "-u", "origin", "main")
                checkouts[name] = path
            source, public, wiki = (checkouts[n] for n in ("source", "public", "wiki"))
            (source / "scripts").mkdir()
            (source / "tests").mkdir()
            (source / "docs").mkdir()
            for name in ("publication.py", "publish.py"):
                shutil.copyfile(scripts / name, source / "scripts" / name)
            (source / "tests/run.php").write_text("<?php exit(0);")
            (source / "docs/index.md").write_text("# Test documentation")
            (source / "mkdocs.yml").write_text("nav:\n  - Start: index.md\n")
            (source / ".gitignore").write_text("__pycache__/\n")
            git(source, "add", ".")
            git(source, "commit", "-m", "Source snapshot")
            values = {
                "mode": "source",
                "publicCheckout": str(public),
                "wikiCheckout": str(wiki),
                "publicUrl": str(root / "public.git"),
                "wikiUrl": str(root / "wiki.git"),
                "publicRoot": git(public, "rev-parse", "HEAD"),
            }
            for name, value in values.items():
                git(source, "config", "publication." + name, value)
            command = [sys.executable, str(source / "scripts/publish.py")]
            first = subprocess.run(command, capture_output=True)
            self.assertEqual(first.returncode, 0, first.stdout.decode())
            self.assertTrue((wiki / "Home.md").exists())
            before = {n: git(p, "rev-parse", "HEAD") for n, p in checkouts.items()}
            second = subprocess.run(command, capture_output=True)
            self.assertEqual(second.returncode, 0, second.stdout.decode())
            self.assertEqual(
                before, {n: git(p, "rev-parse", "HEAD") for n, p in checkouts.items()}
            )
            self.assertNotEqual(before["source"], before["public"])
            (source / "leak.txt").write_text("sk-" + "x" * 30)
            git(source, "add", "leak.txt")
            git(source, "commit", "-m", "Rejected fixture")
            rejected = subprocess.run(command, capture_output=True)
            self.assertNotEqual(rejected.returncode, 0)
            self.assertNotIn(("sk-" + "x" * 30).encode(), rejected.stdout)
            self.assertEqual(
                git(root / "source.git", "rev-parse", "main"), before["source"]
            )
            self.assertEqual(git(public, "rev-parse", "HEAD"), before["public"])


if __name__ == "__main__":
    unittest.main()
