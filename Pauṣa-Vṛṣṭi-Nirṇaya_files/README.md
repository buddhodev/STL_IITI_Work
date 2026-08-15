# Pauṣa Vṛṣṭi Nirṇaya — analysis code

Complete, reproducible analysis for *Reconstructing the daṇḍa-patākā Rain
Calendar of the Kṛṣi-Parāśara (vv. 30–33), and Why It Cannot Be Tested at 23°N.*

Buddhodev Ghosh, from data collected by Damodara Das.

---

## Run it

```bash
pip install numpy pandas scipy matplotlib
python3 run_all.py                # everything, ~2 min
python3 run_all.py 03 04          # selected stages
```

Expects the raw data at `/mnt/user-data/uploads/`. To point elsewhere, edit
`DATA` at the top of `scripts/kp_common.py`.

---

## Layout

```
run_all.py                  master runner
scripts/
  kp_common.py              horology, verse 31, mapping, verification primitives
  01_arithmetic.py          verify the reconstruction (reads no data)
  02_build_forecasts.py     apply the algorithm -> 390 forecast days
  03_verification.py        contingency, skill scores, the degeneracy
  04_power.py               power simulation; replication sample sizing
  05_climatology.py         winter wind sectors, rainfall, seasonal march
  06_superseded.py          the two rejected readings; the lag artefact
  07_figures.py             all six figures as vector PDF
output/                     written by the scripts
```

Stages read CSVs written by earlier stages, so run them in order on a clean
checkout.

---

## Inputs

| File | Rows | Used for |
|---|---|---|
| `weather_data_1hr.csv` | 113,232 | wind direction and speed, hourly |
| `weather_data_24hr.csv` | 4,718 | daily rainfall totals |

Ahmedabad, 23.03°N 72.58°E, 2008-07-01 to 2021-05-31.

---

## The reconstruction (script 01)

| Verse | Statement | Value |
|---|---|---|
| 30 | *sārdhaṁ dinadvayaṁ mānaṁ* | 2.5 d = 150 daṇḍas = **60 h** |
| 32a | *ekaikaṁ pañcadaṇḍena … divaso* | 5 daṇḍas = 2 h = one day → **30 units** |
| 32b | *pūrvārdhaṁ … uttarārdhe ca* | each unit halves at 2.5 daṇḍas = **1 h** |

60 observed hours ↔ 60 half-day forecasts, a bijection. One observation, in
Pauṣa, for Pauṣa's own 30 days.

Verse 31: saumya (N) + vāruṇa (W) → *vṛṣṭi*; pūrva (E) + yāmya (S) → *avṛṣṭi*;
*nirvāta* (calm ≤ 1 km/h) → *vṛṣṭihāni*.

---

## Headline numbers

From `03_verification.py`:

| | |
|---|---|
| Pauṣa forecast days | 390 (13 seasons) |
| Days ≥ 1 mm | **2** (0.51 %) |
| Days with any rain | 8 (2.05 %) |
| Total Pauṣa rain, 13 seasons | **3.6 mm** |
| Seasons at exactly 0.0 mm | **7 of 13** |
| Contingency (a,b,c,d) | 2, 220, 0, 168 |
| Fisher exact | p = 0.508 |
| Predicted fraction | 0.067 → 0.967, mean 0.569 |

The predictor varies; the predictand is almost everywhere zero.

From `04_power.py` — power never exceeds **1.4 %** at any relative risk up to
50×, because two events cannot populate a 2×2 table. This is why the paper
reports no skill statistic.

From `05_climatology.py` — Ahmedabad DJF: N+W 49.8 %, E+S 50.2 % (near-parity);
2.9 mm/season; 0.9 % wet days.

---

## The artefact (script 06)

Superseded reading (iii) produced raw lagged correlations of r = +0.298
(p = 0.0008) at lag 2 and r = +0.379 (p < 10⁻⁴) at lag 3. Both vanish under
anomaly correlation (+0.119, +0.099; p > 0.19). The N+W regime peaks in April
and rainfall in July: two offset annual cycles. n_eff = 7.4 from n = 12.

**Any replication must report anomaly correlations and benchmark against
persistence.** Raw correlations at a Punjab station will look impressive and
mean nothing.

---

## Reproducibility

`04_power.py` is stochastic; seed 20260815, 20,000 simulations per cell. All
other scripts are deterministic. Figures are vector PDF at publication size.

Two corrections were made to the manuscript after this code was written and
the numbers checked against it: the count of zero-rainfall seasons (7, not 9)
and the power figures (the earlier parameterisation let the simulated event
count exceed the observed marginal). The code is canonical where the two
disagree.
