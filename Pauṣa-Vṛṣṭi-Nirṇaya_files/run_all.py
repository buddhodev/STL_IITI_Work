#!/usr/bin/env python3
"""
run_all.py -- reproduce the entire analysis from raw data to figures.

    python3 run_all.py            run everything
    python3 run_all.py 03 04      run selected stages only

Stages must be run in order on a clean checkout, since later stages read
CSVs written by earlier ones.
"""
import subprocess
import sys
import time
from pathlib import Path

HERE = Path(__file__).resolve().parent
SCRIPTS = HERE / "scripts"

STAGES = [
    ("01", "01_arithmetic.py",      "Arithmetic reconstruction of vv. 30-33"),
    ("02", "02_build_forecasts.py", "Build the 390 Pausa forecast days"),
    ("03", "03_verification.py",    "Verification: the test is degenerate"),
    ("04", "04_power.py",           "Power analysis and replication sizing"),
    ("05", "05_climatology.py",     "Winter wind and rainfall climatology"),
    ("06", "06_superseded.py",      "Rejected readings; annual-cycle artefact"),
    ("07", "07_figures.py",         "Generate all figures"),
]


def main():
    want = sys.argv[1:]
    stages = [s for s in STAGES if not want or s[0] in want]

    print("=" * 74)
    print("KRSI-PARASARA PAUSA-VRSTI-NIRNAYA -- FULL ANALYSIS")
    print("=" * 74)
    for tag, script, desc in stages:
        print(f"  {tag}  {desc}")

    t0 = time.time()
    for tag, script, desc in stages:
        print("\n" + "#" * 74)
        print(f"# STAGE {tag} -- {desc}")
        print("#" * 74)
        r = subprocess.run([sys.executable, script], cwd=SCRIPTS)
        if r.returncode != 0:
            print(f"\nFAILED at stage {tag}")
            sys.exit(r.returncode)

    print("\n" + "=" * 74)
    print(f"COMPLETE in {time.time() - t0:.1f} s")
    print(f"Outputs in {HERE / 'output'}")
    print("=" * 74)


if __name__ == "__main__":
    main()
