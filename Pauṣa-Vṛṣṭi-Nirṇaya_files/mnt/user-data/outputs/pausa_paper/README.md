# Pauṣa Vṛṣṭi Nirṇaya — LaTeX package

Reconstructing the *daṇḍa-patākā* rain calendar of *Kṛṣi-Parāśara* vv. 30–33,
and why it cannot be tested at 23°N.

**Buddhodev Ghosh**, from data collected by **Damodara Das**.

## Compile
```
pdflatex pausa_vrsti_nirnaya.tex
pdflatex pausa_vrsti_nirnaya.tex
```
Requires `newtxtext`, `booktabs`, `longtable`, `fancyhdr`, `microtype` (TeX Live full).
Overleaf: upload folder, compiler = pdfLaTeX.

## The reading
One observation, in Pauṣa, for Pauṣa. Nothing beyond the month.
- 2½ days = 150 daṇḍas = 60 h, from Pauṣa śukla pratipadā sunrise
- 5 daṇḍas (2 h) = one day of Pauṣa → 30 days (v. 32)
- first 2½ daṇḍas (1 h) = that day's *vāsarī vṛṣṭi*; second 2½ = same day's *naiśikī*
- 60 hours observed ↔ 60 half-day forecasts

## Headline result: the test is degenerate
| | |
|---|---|
| Pauṣa forecast days | 390 (13 seasons) |
| Days ≥1 mm | **2** (0.51%) |
| Total Pauṣa rain, 13 seasons | **3.6 mm** |
| Seasons with exactly 0.0 mm | **7 of 13** |
| Contingency | 2 hits, 220 false alarms, 0 misses, 168 CN |
| Fisher exact | p = 0.508 |
| Power, any effect size up to RR=50 | **≤1.4%** |

No skill statistic is reported, because none would mean anything.

## Code
`code/` contains the complete reproducible analysis — seven numbered scripts,
a shared module, and a master runner. `cd code && python3 run_all.py`
regenerates every figure and every number in the paper from the raw data
(~2 min). See `code/README.md`.

## Contents
| File | Description |
|---|---|
| `pausa_vrsti_nirnaya.tex` | Manuscript |
| `f1_mapping.pdf` | The Pauṣa-only mapping |
| `f2_compass.pdf` | Verse 31 directional key |
| `f3_degeneracy.pdf` | Observed vs predicted; base-rate comparison |
| `f4_power.pdf` | Power curve |
| `f5_wd.pdf` | Western disturbance vs verse 31 |
| `f6_provenance.pdf` | Provenance vs test site |
| `pausa_daily_forecasts.csv` | 390 days, paired day/night predictions + observed rain |
| `code/` | Full reproducible analysis (7 scripts + runner) |

## Replication specification (§6.3)
Sialkot + Jammu; ERA5 hourly winds validated against NOAA ISD; IMD 0.25° gridded
rainfall; ~70 Pauṣa seasons 1950–2020; anomaly-based occurrence scoring with
Peirce/Heidke, N_eff correction, block bootstrap, benchmarked against climatology
**and persistence**.

Base rate is the binding constraint: 0.15 wet days/month (Ahmedabad) → 3.5 (Jammu)
is a ~20× gain in predictand variance. Site choice beats record length.
