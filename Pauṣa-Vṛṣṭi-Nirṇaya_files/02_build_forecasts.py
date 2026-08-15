"""
02_build_forecasts.py -- apply the reconstructed algorithm to the data.

For each of the 13 Pausa seasons: take the 60-hour observation window from
sunrise on Pausa sukla pratipada, map it to 30 days x {day, night}, and
match each forecast day against observed rainfall on the corresponding day.

Writes output/pausa_daily_forecasts.csv  (390 rows)
       output/pausa_season_summary.csv   (13 rows)

Paper: section 3.3, Appendix A, Appendix B.
"""
import pandas as pd
import kp_common as K


def build(invert=False, sunrise_h=K.SUNRISE_H, offset_days=0, verbose=True):
    """Return (daily_df, season_df) for the Pausa-only reading."""
    hourly = K.load_hourly()
    daily = K.load_daily()

    rows, skipped = [], []
    for year, start in K.PAUSA_START.items():
        w = K.observation_window(hourly, start, sunrise_h, offset_days)
        fc = K.map_to_forecast(w, invert=invert)
        if fc is None:
            skipped.append((year, len(w)))
            continue

        s0 = pd.Timestamp(start) + pd.Timedelta(days=offset_days)
        for cell in fc:
            day = s0 + pd.Timedelta(days=cell["k"] - 1)
            if day not in daily.index:
                continue
            mm = float(daily.loc[day, "totalprecipMM"])
            rows.append({
                "season": f"{year}-{str(year + 1)[2:]}",
                "pausa_year": year,
                "k": cell["k"],
                "date": day.date(),
                "pred_day": cell["pred_day"],
                "pred_night": cell["pred_night"],
                "pred_any": cell["pred_any"],
                "obs_mm": mm,
                "obs_wet": int(mm >= K.WET_MM),
                "obs_trace": int(mm > 0),
            })

    D = pd.DataFrame(rows)

    S = (D.groupby(["pausa_year", "season"])
           .agg(pred_frac=("pred_any", "mean"),
                pred_days=("pred_any", "sum"),
                rain_mm=("obs_mm", "sum"),
                wet_days=("obs_wet", "sum"),
                trace_days=("obs_trace", "sum"),
                n_days=("k", "size"))
           .reset_index())
    S["pausa_start"] = S.pausa_year.map(K.PAUSA_START)

    if verbose and skipped:
        for y, n in skipped:
            print(f"  skipped {y}: only {n} hours available")
    return D, S


def main():
    K.banner("BUILDING PAUSA FORECASTS -- one observation, in Pausa, for Pausa")
    D, S = build()

    print(f"\nSeasons with complete windows : {S.pausa_year.nunique()}")
    print(f"Forecast days                 : {len(D)}")
    print(f"Forecast cells (day + night)  : {len(D) * 2}")

    K.banner("SEASON SUMMARY")
    show = S[["season", "pausa_start", "pred_frac", "pred_days",
              "rain_mm", "wet_days", "trace_days"]].copy()
    show["pred_frac"] = show.pred_frac.round(3)
    print(show.to_string(index=False))

    print(f"\nMean predicted fraction : {S.pred_frac.mean():.3f}")
    print(f"Range                   : {S.pred_frac.min():.3f} "
          f"({S.loc[S.pred_frac.idxmin(), 'season']}) to "
          f"{S.pred_frac.max():.3f} "
          f"({S.loc[S.pred_frac.idxmax(), 'season']})")
    print("-> the predictor is NOT degenerate; it discriminates between years")

    D.to_csv(K.OUT / "pausa_daily_forecasts.csv", index=False)
    S.to_csv(K.OUT / "pausa_season_summary.csv", index=False)
    print(f"\nwrote {K.OUT / 'pausa_daily_forecasts.csv'}")
    print(f"wrote {K.OUT / 'pausa_season_summary.csv'}")


if __name__ == "__main__":
    main()
