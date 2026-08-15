"""
06_superseded.py -- the two rejected structural readings, and the
annual-cycle artefact that reading (iii) produced.

Reading (ii):  one continuous 30-day vigil from Pausa; 12 blocks of 2.5 d
               encoding the 12 months of the FOLLOWING year.
Reading (iii): the 2.5-day observation recurring at EVERY month's opening,
               each month forecasting itself.

Both are wrong. They are retained because (a) a reader might independently
make either, and (b) reading (iii) generated raw lagged correlations of
r = +0.41 at p < 1e-4 that were entirely an artefact of two offset annual
cycles -- a trap any replication must avoid.

Paper: Appendix C, section 6.3 (the warning to replications).
"""
import numpy as np
import pandas as pd
from scipy import stats
import kp_common as K


# --------------------------------------------------------------------------
# READING (ii) -- one annual vigil, 12 blocks, forecasting 12 months
# --------------------------------------------------------------------------
def reading_ii(hourly, daily, invert=False, offset_days=0,
               sunrise_h=K.SUNRISE_H):
    rows = []
    for year, start in K.PAUSA_START.items():
        w = K.observation_window(hourly, start, sunrise_h, offset_days,
                                 hours=720)                 # 30 days
        if len(w) < 720:
            continue
        pred = np.array([K.verse31(a, b, invert) for a, b in
                         zip(w.winddirdegree, w.windspeedKmph)])
        s0 = pd.Timestamp(start)
        for m in range(12):
            st = s0 + pd.Timedelta(days=K.SYNODIC * m)
            sl = daily.loc[st: st + pd.Timedelta(days=K.SYNODIC)]
            if len(sl) < 25:
                continue
            rows.append({"year": year, "m": m,
                         "pred": pred[m * 60:(m + 1) * 60].mean(),
                         "obs_mm": sl.totalprecipMM.sum(),
                         "obs_days": int((sl.totalprecipMM >= K.WET_MM).sum())})
    return pd.DataFrame(rows)


# --------------------------------------------------------------------------
# READING (iii) -- 2.5 d at each month's opening, forecasting that month
# --------------------------------------------------------------------------
def reading_iii(hourly, daily, invert=False):
    rows = []
    for year, start in K.PAUSA_START.items():
        s0 = pd.Timestamp(start)
        for m in range(12):
            mstart = s0 + pd.Timedelta(days=K.SYNODIC * m)
            w = K.observation_window(hourly, mstart.normalize())
            fc = K.map_to_forecast(w, invert=invert)
            if fc is None:
                continue
            sl = daily.loc[mstart: mstart + pd.Timedelta(days=K.SYNODIC)]
            if len(sl) < 25:
                continue
            rows.append({"year": year, "m": m,
                         "pred": np.mean([c["pred_any"] for c in fc]),
                         "obs_mm": sl.totalprecipMM.sum(),
                         "obs_days": int((sl.totalprecipMM >= K.WET_MM).sum())})
    return pd.DataFrame(rows)


def report(df, label):
    r, p = stats.pearsonr(df.pred, df.obs_mm)
    rho, p2 = stats.spearmanr(df.pred, df.obs_mm)
    med_p, med_o = df.pred.median(), df.obs_mm.median()
    a, b, c, d = K.contingency(df.pred > med_p, df.obs_mm > med_o)
    sk = K.skill_scores(a, b, c, d)
    print(f"  {label}")
    print(f"    n = {len(df)}   Pearson r = {r:+.3f} (p = {p:.3f})   "
          f"Spearman rho = {rho:+.3f} (p = {p2:.3f})")
    print(f"    accuracy = {sk['accuracy']:.4f}   HSS = {sk['HSS']:+.4f}   "
          f"PSS = {sk['PSS']:+.4f}")
    return r


def lag_analysis(df):
    """Raw vs anomaly correlation at each lag -- the artefact, exposed."""
    out = []
    for L in range(4):
        rows = []
        for _, r in df.iterrows():
            t = df[(df.year == r.year) & (df.m == r.m + L)]
            if len(t):
                rows.append({"m_p": r.m, "m_t": r.m + L, "pred": r.pred,
                             "obs": t.obs_mm.values[0]})
        A = pd.DataFrame(rows)
        if not len(A):
            continue
        # anomalies: remove the month-of-year climatology from BOTH sides
        A["pa"] = A.pred - A.groupby("m_p").pred.transform("mean")
        A["oa"] = A.obs - A.groupby("m_t").obs.transform("mean")
        r_raw, p_raw = stats.pearsonr(A.pred, A.obs)
        r_an, p_an = stats.pearsonr(A.pa, A.oa)
        out.append({"lag": L, "n": len(A), "raw_r": r_raw, "raw_p": p_raw,
                    "anom_r": r_an, "anom_p": p_an})
    return pd.DataFrame(out)


def main():
    hourly = K.load_hourly()
    daily = K.load_daily()

    K.banner("READING (ii) -- one 30-day vigil forecasting the year")
    ii = reading_ii(hourly, daily)
    report(ii, "as written")
    inv = reading_ii(hourly, daily, invert=True)
    r_inv, _ = stats.pearsonr(inv.pred, inv.obs_mm)
    print(f"    verse 31 inverted: r = {r_inv:+.3f}  "
          "(mirror image -- absence of signal, not wrong sign)")

    print("\n  pancanga sensitivity:")
    for off in [-2, -1, 0, 1, 2]:
        s = reading_ii(hourly, daily, offset_days=off)
        r, p = stats.pearsonr(s.pred, s.obs_mm)
        print(f"    offset {off:+d} d :  r = {r:+.3f}  (p = {p:.3f})")
    print("  sunrise-hour sensitivity:")
    for sr in [6, 7, 8]:
        s = reading_ii(hourly, daily, sunrise_h=sr)
        r, p = stats.pearsonr(s.pred, s.obs_mm)
        print(f"    sunrise {sr:02d}:00 :  r = {r:+.3f}  (p = {p:.3f})")

    K.banner("READING (iii) -- 2.5 d per month, each month forecasting itself")
    iii = reading_iii(hourly, daily)
    report(iii, "as written")

    print("\n  within-month interannual correlation (the clean test):")
    nsig = 0
    for m in range(12):
        s = iii[iii.m == m]
        if len(s) < 5:
            continue
        r, p = stats.pearsonr(s.pred, s.obs_mm)
        star = " *" if p < 0.05 else ""
        nsig += p < 0.05
        print(f"    {K.LUNAR_MONTHS[m]:<12} n={len(s):2d}  r = {r:+.3f}  "
              f"p = {p:.3f}{star}")
    print(f"    significant at 0.05: {nsig}/12 "
          f"(expected by chance ~0.6; Bonferroni alpha = 0.0042)")

    K.banner("THE ARTEFACT -- raw vs anomaly correlation by lag")
    L = lag_analysis(iii)
    print(f"  {'lag':>4}{'n':>6}{'raw r':>10}{'raw p':>11}"
          f"{'anom r':>10}{'anom p':>10}")
    for _, r in L.iterrows():
        flag = "  <== spurious" if r.raw_p < 0.01 else ""
        print(f"  {int(r.lag):>4}{int(r.n):>6}{r.raw_r:>+10.3f}"
              f"{r.raw_p:>11.4f}{r.anom_r:>+10.3f}{r.anom_p:>10.3f}{flag}")
    L.to_csv(K.OUT / "lag_artefact.csv", index=False)

    K.banner("EFFECTIVE DEGREES OF FREEDOM")
    g = iii.groupby("m").agg(pred=("pred", "mean"), obs=("obs_mm", "mean"))
    r1 = pd.Series(g.pred.values).autocorr(1)
    r2 = pd.Series(g.obs.values).autocorr(1)
    ne = K.n_eff(12, r1, r2)
    print(f"  lag-1 autocorrelation: predictor = {r1:+.3f}, "
          f"target = {r2:+.3f}")
    print(f"  n_eff = n(1 - r1 r2)/(1 + r1 r2) = {ne:.1f} from n = 12")
    print("\n  The apparent climatological significance rested on roughly")
    print("  four independent points. Both predictor and predictand carry")
    print("  a strong annual cycle; correlating two offset seasonal marches")
    print("  manufactures significance from nothing. Any replication MUST")
    print("  report anomaly correlations and benchmark against persistence.")


if __name__ == "__main__":
    main()
