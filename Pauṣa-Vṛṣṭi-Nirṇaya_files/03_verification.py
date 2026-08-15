"""
03_verification.py -- verify the Pausa forecasts, and establish that the
test is statistically degenerate.

This is the paper's central result. The predictor varies; the predictand
is almost everywhere zero. No skill statistic computed on this sample
carries information about the method.

Paper: section 4.2, Table 2, Appendix B.
"""
import numpy as np
import pandas as pd
from scipy import stats
import kp_common as K


def main():
    D = pd.read_csv(K.OUT / "pausa_daily_forecasts.csv")
    S = pd.read_csv(K.OUT / "pausa_season_summary.csv")

    K.banner("THE PREDICTAND")
    n = len(D)
    wet = int(D.obs_wet.sum())
    trace = int(D.obs_trace.sum())
    total = D.obs_mm.sum()
    zero_seasons = int((S.rain_mm == 0).sum())

    print(f"  Pausa forecast days              : {n}")
    print(f"  Days with >= {K.WET_MM} mm             : {wet}  "
          f"({wet / n * 100:.2f} %)")
    print(f"  Days with any measurable rain    : {trace}  "
          f"({trace / n * 100:.2f} %)")
    print(f"  Total Pausa rainfall, 13 seasons : {total:.1f} mm")
    print(f"  Mean per season                  : {total / len(S):.2f} mm")
    print(f"  Seasons recording exactly 0.0 mm : {zero_seasons} of {len(S)}")

    K.banner("EVERY PAUSA DAY WITH MEASURABLE RAIN (paper Appendix B)")
    w = D[D.obs_mm > 0][["season", "date", "k", "pred_day",
                         "pred_night", "obs_mm", "obs_wet"]]
    print(w.to_string(index=False))

    K.banner(f"CONTINGENCY TABLE  (wet day = >= {K.WET_MM} mm)")
    a, b, c, d = K.contingency(D.pred_any, D.obs_wet)
    print(f"  hits (a)               = {a}")
    print(f"  false alarms (b)       = {b}")
    print(f"  misses (c)             = {c}")
    print(f"  correct negatives (d)  = {d}")

    sk = K.skill_scores(a, b, c, d)
    print(f"\n  accuracy               = {sk['accuracy']:.4f}")
    print(f"  base rate (obs)        = {sk['base_rate']:.4f}")
    print(f"  forecast rate          = {sk['fcst_rate']:.4f}")
    print(f"  HSS                    = {sk['HSS']:+.4f}")
    print(f"  PSS                    = {sk['PSS']:+.4f}")

    odds, p_fisher = stats.fisher_exact([[a, b], [c, d]])
    print(f"\n  Fisher exact  p        = {p_fisher:.3f}   "
          f"(odds ratio = {odds})")
    print("\n  Both wet days fell on days predicted rainy -- a perfect hit")
    print(f"  rate and zero misses -- but alongside {b} false alarms this")
    print("  carries no evidential weight. A rule predicting rain on "
          f"{sk['fcst_rate'] * 100:.0f}% of")
    print("  days will capture both members of a two-element event set")
    print("  more often than not by construction.")

    K.banner("SAME TEST AT THE TRACE THRESHOLD (> 0 mm)")
    a2, b2, c2, d2 = K.contingency(D.pred_any, D.obs_trace)
    sk2 = K.skill_scores(a2, b2, c2, d2)
    _, p2 = stats.fisher_exact([[a2, b2], [c2, d2]])
    print(f"  a={a2} b={b2} c={c2} d={d2}")
    print(f"  accuracy = {sk2['accuracy']:.4f}   HSS = {sk2['HSS']:+.4f}   "
          f"PSS = {sk2['PSS']:+.4f}   Fisher p = {p2:.3f}")

    K.banner("DAY vs NIGHT HALVES (verse 32)")
    for col, label in [("pred_day", "purvardha (vasari vrsti)"),
                       ("pred_night", "uttarardha (naisiki)")]:
        aa, bb, cc, dd = K.contingency(D[col], D.obs_wet)
        s = K.skill_scores(aa, bb, cc, dd)
        agree = (D.pred_day == D.pred_night).mean()
        print(f"  {label:28s} fcst rate = {D[col].mean():.4f}   "
              f"HSS = {s['HSS']:+.4f}")
    print(f"  day/night agreement in the same unit: {agree * 100:.1f} %")

    K.banner("SEASON-LEVEL TEST (n = 13)")
    r, p = stats.pearsonr(S.pred_frac, S.rain_mm)
    rho, p2 = stats.spearmanr(S.pred_frac, S.rain_mm)
    print(f"  predicted fraction vs season rainfall")
    print(f"    Pearson  r   = {r:+.3f}  (p = {p:.3f})")
    print(f"    Spearman rho = {rho:+.3f}  (p = {p2:.3f})")
    ties = (S.rain_mm == 0).mean()
    print(f"  {ties * 100:.0f}% of target values are tied at zero; "
          f"variance = {S.rain_mm.var():.4f} mm^2")

    K.banner("CONCLUSION")
    print("  The correct statement is not that the danda-pataka failed at")
    print("  Ahmedabad, but that Ahmedabad cannot adjudicate it. No skill")
    print("  statistic is reported in the paper, because none computed on")
    print("  this sample would mean anything. See 04_power.py.")


if __name__ == "__main__":
    main()
