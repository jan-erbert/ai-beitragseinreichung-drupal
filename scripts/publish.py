#!/usr/bin/env python3
"""Veröffentlicht geprüfte Snapshots nach Forgejo, GitHub und in das GitHub-Wiki."""

import argparse
import json
import os
import subprocess
from pathlib import Path

from publication import MANIFEST, check_files, wiki_pages, write_managed


def git(root, *args, check=True):
    """Führt einen Git-Schritt ohne Shell und ohne Ausgabe von Zugangsdaten aus."""
    return subprocess.run(
        ["git", "-C", str(root), *args],
        check=check,
        capture_output=True,
        env={**os.environ, "JE_PUBLISH_COORDINATED": "1"},
    )


def setting(root, name):
    """Liest eine notwendige lokale Veröffentlichungseinstellung."""
    result = git(root, "config", "--get", "publication." + name, check=False)
    value = result.stdout.decode().strip()
    if not value:
        raise ValueError("Lokale Git-Einstellung fehlt: publication." + name)
    return value


def clean(root):
    """Verhindert das Überschreiben ungesicherter Änderungen."""
    if git(root, "status", "--porcelain").stdout:
        raise ValueError(f"Arbeitsbaum nicht sauber: {root.name}")


def tracked(root):
    """Liest ausschließlich eingecheckte Dateien des aktuellen Commits."""
    names = (
        git(root, "ls-tree", "-r", "--name-only", "-z", "HEAD")
        .stdout.decode()
        .split("\0")
    )
    return {name: git(root, "show", "HEAD:" + name).stdout for name in names if name}


def commit_if_changed(root, message):
    """Commitet ausschließlich die vorbereiteten Änderungen im eigenen Checkout."""
    git(root, "add", "--all")
    changed = git(root, "diff", "--cached", "--quiet", check=False)
    if changed.returncode == 1:
        git(root, "commit", "-m", message)
    elif changed.returncode:
        raise ValueError("Git-Diff konnte nicht geprüft werden.")


def main():
    """Prüft alle Ziele vor dem ersten Push und aktualisiert sie nachvollziehbar."""
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--check", action="store_true", help="Nur prüfen, nichts kopieren oder pushen"
    )
    parser.add_argument("--from-pre-push", action="store_true", help=argparse.SUPPRESS)
    args = parser.parse_args()
    source = Path(__file__).resolve().parent.parent
    if setting(source, "mode") != "source":
        raise ValueError(
            "Dieser Checkout ist nicht als Veröffentlichungsquelle eingerichtet."
        )
    public = Path(setting(source, "publicCheckout")).expanduser().resolve()
    wiki = Path(setting(source, "wikiCheckout")).expanduser().resolve()
    public_url = setting(source, "publicUrl")
    wiki_url = setting(source, "wikiUrl")
    if len({source, public, wiki}) != 3:
        raise ValueError("Quelle, öffentliche Kopie und Wiki müssen getrennt sein.")
    for root in (source, public, wiki):
        clean(root)
    if git(source, "branch", "--show-current").stdout.strip() != b"main":
        raise ValueError("Veröffentlicht wird ausschließlich der Hauptbranch main.")
    for root, expected in ((public, public_url), (wiki, wiki_url)):
        if git(root, "remote", "get-url", "origin").stdout.decode().strip() != expected:
            raise ValueError("Unerwartetes Veröffentlichungsziel: " + root.name)
    if git(public, "branch", "--show-current").stdout.strip() != b"main":
        raise ValueError("Öffentliche Kopie muss auf main stehen.")
    anchor = setting(source, "publicRoot")
    if git(
        public, "merge-base", "--is-ancestor", anchor, "HEAD", check=False
    ).returncode:
        raise ValueError(
            "Öffentliche Historie entspricht nicht der freigegebenen Kopie."
        )
    forbidden = (
        git(source, "config", "--get-all", "publication.forbiddenPattern", check=False)
        .stdout.decode()
        .splitlines()
    )
    files = tracked(source)
    check_files(files, forbidden)
    generated = wiki_pages(files, public_url)
    check_files(generated, forbidden)
    subprocess.run(["php", "tests/run.php"], cwd=source, check=True)
    if args.check:
        print("Geprüft: Commit, Zielordner, öffentliche Dateien und Wiki. Kein Push.")
        return
    for root in ((public, wiki) if args.from_pre_push else (source, public, wiki)):
        git(root, "pull", "--ff-only")
    # Ein inzwischen übernommener Commit muss erneut geprüft werden.
    if tracked(source) != files:
        raise ValueError("Quellstand wurde aktualisiert. Bitte erneut veröffentlichen.")
    old_public = tracked(public)
    write_managed(public, files, old_public)
    commit_if_changed(
        public,
        "Publish source snapshot "
        + git(source, "rev-parse", "--short", "HEAD").stdout.decode().strip(),
    )
    manifest = wiki / MANIFEST
    old_wiki = json.loads(manifest.read_text()) if manifest.exists() else []
    write_managed(wiki, generated, old_wiki)
    commit_if_changed(wiki, "Synchronize documentation from docs")
    for root in ((public, wiki) if args.from_pre_push else (source, public, wiki)):
        git(root, "push", "origin", "HEAD")
        print("Aktualisiert: " + root.name)
    if args.from_pre_push:
        print("Öffentliche Kopie und Wiki aktualisiert; Quell-Push wird fortgesetzt.")
    else:
        print("Alle drei Ziele aktualisiert. CI-Ergebnisse anschließend kontrollieren.")


if __name__ == "__main__":
    try:
        main()
    except (ValueError, subprocess.CalledProcessError, OSError) as error:
        # Befehlsausgaben könnten fremde Servertexte oder Zugangsdaten enthalten.
        if isinstance(error, subprocess.CalledProcessError):
            print(
                "Veröffentlichung bei einem Git-/Prüfschritt gestoppt. "
                "Bereits erfolgreiche Pushes bleiben erhalten; "
                "nach Prüfung erneut starten."
            )
        else:
            print(str(error))
        raise SystemExit(1) from None
