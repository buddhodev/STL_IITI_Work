"""
04_power.py -- how large a true effect would this sample detect?

Holds the observed predictor sequence fixed and injects a true elevation
of the hit rate on predicted-rain days, preserving the overall base rate.
Reports the fraction of simulations reaching p < 0.05 by Fisher's exact test.

Paper: section 4.2, Table 1, Figure 4.
"""
import numpy as np
import pandas as pd
from scipy import stats
import kp_common as K

N_SIM = 20000
SEED = 20260815
RELATIVE_RISKS = [1, 2, 3, 5, 10, 20, 50]


def simulate(pred, base, rr, n_sim=N_SIM, seed=SEED, alpha=0.05):
    """Power to detect a relative risk `rr` between predicted and other days.

    Parameterised so the marginal event rate stays exactly at `base`:

        p_lo = base / (f*rr + 1 - f),   p_hi = rr * p_lo

    where f is the fraction of days forecast rainy. This is well defined
    for any rr >= 1, unlike fixing p_hi directly, which becomes infeasible
    once f*rr*base exceeds base.
    """
    rng = np.random.default_rng(seed)
    pred = np.asarray(pred).astype(bool)
    f = pred.mean()

    p_lo = base / (f * rr + 1 - f)
    p_hi = rr * p_lo
    if p_hi > 1:
        return np.nan

    p_vec = np.where(pred, p_hi, p_lo)
    hits = 0
    for _ in range(n_sim):
        y = rng.random(len(p_vec)) < p_vec
        a = int((pred & y).sum())
        b = int((pred & ~y).sum())
        c = int((~pred & y).sum())
        d = int((~pred & ~y).sum())
        if stats.fisher_exact([[a, b], [c, d]])[1] < alpha:
            hits += 1
    return hits / n_sim


def main():
    D = pd.read_csv(K.OUT / "pausa_daily_forecasts.csv")
    n = len(D)
    events = int(D.obs_wet.sum())
    base = events / n

    K.banner("STATISTICAL POWER AT AHMEDABAD")
    print(f"  n days      = {n}")
    print(f"  events      = {events}")
    print(f"  base rate   = {base * 100:.2f} %")
    print(f"  fcst rate   = {D.pred_any.mean() * 100:.1f} %")
    print(f"  simulations = {N_SIM} per cell, seed = {SEED}")

    f = D.pred_any.mean()
    print(f"\n  {'rel. risk':>10}  {'rate on pred days':>18}  "
          f"{'on other days':>14}  {'power':>8}")
    rows = []
    for rr in RELATIVE_RISKS:
        pw = simulate(D.pred_any, base, rr)
        p_lo = base / (f * rr + 1 - f)
        rows.append({"relative_risk": rr, "p_predicted": rr * p_lo,
                     "p_other": p_lo, "power": pw})
        pw_s = "n/a" if np.isnan(pw) else f"{pw * 100:6.1f} %"
        print(f"  {rr:>9}x  {rr * p_lo * 100:>17.2f} %  "
              f"{p_lo * 100:>13.2f} %  {pw_s:>8}")

    P = pd.DataFrame(rows)
    P.to_csv(K.OUT / "power_curve.csv", index=False)

    K.banner("READING")
    print("  A method would have to be nearly deterministic before this")
    print("  dataset could distinguish it from chance: with only 2 events,")
    print("  even a large true relative risk leaves the contingency table")
    print("  indistinguishable from chance under Fisher's exact test.")
    print("\n  This is why the paper reports no skill statistic for the")
    print("  method: the sample cannot adjudicate it either way.")

    K.banner("WHAT WOULD BE NEEDED -- base rate is the binding constraint")
    print(f"  {'site':<26}{'wet d/month':>12}{'p':>10}{'variance':>12}")
    sites = [("Ahmedabad (this study)", 0.15),
             ("Delhi Palam", 1.4),
             ("Ludhiana", 2.2),
             ("Amritsar", 2.4),
             ("Dehradun", 2.8),
             ("Jammu", 3.5)]
    v0 = None
    for name, wd in sites:
        p = wd / 30
        v = p * (1 - p)
        if v0 is None:
            v0 = v
        print(f"  {name:<26}{wd:>12.2f}{p:>10.4f}{v / v0:>11.1f}x")
    print("\n  Moving from Ahmedabad to Jammu multiplies the variance of a")
    print("  binomial predictand ~20-fold. Site selection is a higher-")
    print("  leverage design decision than record length.")

    K.banner("SEASONS REQUIRED AT A REPLICATION SITE")
    print("  Power to detect relative risk RR = 2.0 at alpha = 0.05, by")
    print("  site base rate and number of Pausa seasons. Each season")
    print("  contributes 30 days, but the days are autocorrelated: the")
    print("  effective independent sample is nearer the number of synoptic")
    print("  episodes (4-7 western disturbances per winter month), so these")
    print("  are optimistic and should be read as lower bounds on n.")
    print(f"\n  {'site':<24}{'p':>8}" +
          "".join(f"{s:>9}" for s in [10, 20, 30, 50, 70]))
    rng_pred_frac = 0.5
    for name, wd in [("Amritsar", 2.4), ("Dehradun", 2.8), ("Jammu", 3.5)]:
        p = wd / 30
        line = f"  {name:<24}{p:>8.3f}"
        for nseas in [10, 20, 30, 50, 70]:
            ndays = nseas * 30
            pred = np.zeros(ndays, dtype=bool)
            pred[: int(ndays * rng_pred_frac)] = True
            pw = simulate(pred, p, 2.0, n_sim=2000)
            line += f"{pw * 100:>8.0f}%"
        print(line)
    print("\n  ~30-60 seasons are needed for a moderate effect, which the")
    print("  1950-2020 ERA5/IMD window (~70 seasons) accommodates and")
    print("  which 13 seasons never could.")


if __name__ == "__main__":
    main()
