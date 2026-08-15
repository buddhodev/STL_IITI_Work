"""
05_climatology.py -- why the predictand is absent at 23 N.

Characterises the Ahmedabad winter regime: the verse-31 sectors occur with
near-parity, and the winter rainfall they would indicate is essentially
absent. Also computes the seasonal march used in 06_superseded.py to
explain the spurious lagged correlations.

Paper: sections 4.3, 6.2, Figure 6b.
"""
import numpy as np
import pandas as pd
import kp_common as K

MONTH_ABBR = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
              "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]


def main():
    hourly = K.load_hourly()
    daily = K.load_daily()

    hourly["sector"] = [K.sector(t) for t in hourly.winddirdegree]
    hourly["rain_sector"] = hourly.sector.isin(["saumya", "varuna"])
    hourly["month"] = hourly.date.dt.month
    daily["month"] = daily.index.month
    daily["year"] = daily.index.year

    K.banner("AHMEDABAD DJF WIND SECTOR FREQUENCY")
    djf = hourly[hourly.month.isin([12, 1, 2])]
    freq = djf.sector.value_counts(normalize=True) * 100
    names = {"saumya": "saumya (N)", "purva": "purva  (E)",
             "yamya": "yamya  (S)", "varuna": "varuna (W)"}
    for s in ["purva", "saumya", "varuna", "yamya"]:
        print(f"  {names[s]:<14} {freq.get(s, 0):5.1f} %")
    nw = djf.rain_sector.mean() * 100
    print(f"\n  verse 31 rain sectors   (N + W) = {nw:.1f} %")
    print(f"  verse 31 drought sectors (E + S) = {100 - nw:.1f} %")
    print("\n  Near-parity: the rule partitions the winter record almost")
    print("  evenly, by construction, while the partition tracks nothing.")

    K.banner("AHMEDABAD DJF PRECIPITATION")
    djf_d = daily[daily.month.isin([12, 1, 2])]
    per_season = djf_d.groupby(djf_d.index.year).totalprecipMM.sum()
    wet_days = int((djf_d.totalprecipMM >= K.WET_MM).sum())
    print(f"  mean DJF rainfall        = {per_season.mean():.1f} mm/season")
    print(f"  DJF days >= {K.WET_MM} mm        = {wet_days} of {len(djf_d)} "
          f"({wet_days / len(djf_d) * 100:.1f} %)")
    print("\n  There is no winter precipitation regime at 23 N for a")
    print("  winter-rain rule to be about.")

    K.banner("SEASONAL MARCH -- the phase lead behind spurious lag skill")
    march = []
    for m in range(1, 13):
        h = hourly[hourly.month == m]
        d = daily[daily.month == m]
        march.append({
            "month": MONTH_ABBR[m - 1],
            "pct_NW": h.rain_sector.mean() * 100,
            "mean_mm": d.groupby(d.year).totalprecipMM.sum().mean(),
        })
    M = pd.DataFrame(march)
    print(f"  {'month':>6}{'% hours N+W':>14}{'mean rainfall (mm)':>21}")
    for _, r in M.iterrows():
        print(f"  {r['month']:>6}{r['pct_NW']:>13.1f}%{r['mean_mm']:>20.1f}")

    peak_wind = M.loc[M.pct_NW.idxmax(), "month"]
    peak_rain = M.loc[M.mean_mm.idxmax(), "month"]
    print(f"\n  N+W regime peaks in {peak_wind}; rainfall peaks in {peak_rain}.")
    print("  The southwesterly regime establishes itself months before the")
    print("  rains. Correlating these two seasonal marches at a lag")
    print("  manufactures significance from nothing -- see 06_superseded.py.")

    M.to_csv(K.OUT / "seasonal_march.csv", index=False)

    K.banner("PUBLISHED WINTER NORMALS FOR THE 30-34 N BELT")
    print("  IMD defines a rainy day as >= 2.5 mm; commercial aggregators")
    print("  generally use >= 1 mm and are NOT directly comparable.")
    print(f"\n  {'station':<22}{'source':<18}{'Dec mm':>8}{'Dec d':>7}"
          f"{'Jan mm':>8}{'Jan d':>7}")
    belt = [
        ("Jammu (32.7N)", "IMD 1991-2020", 21.9, 1.5, 67.9, 3.5),
        ("Dehradun (30.3N)", "IMD 1991-2020", 13.0, 1.1, 43.5, 2.8),
        ("Amritsar (31.6N)", "IMD 1951-1980", 14.8, 1.2, 28.0, 2.4),
        ("Ludhiana (30.9N)", "IMD 1951-1980", 14.4, 1.1, 26.5, 2.2),
        ("Sialkot (32.5N)", "commercial >=1mm", 15.0, 3.0, 46.0, 5.0),
        ("Lahore (31.5N)", "commercial >=1mm", 15.0, 2.5, 29.0, 3.5),
        ("Delhi Palam (28.6N)", "IMD 1991-2020", 5.8, 0.4, 17.2, 1.4),
    ]
    for name, src, dm, dd, jm, jd in belt:
        print(f"  {name:<22}{src:<18}{dm:>8.1f}{dd:>7.1f}{jm:>8.1f}{jd:>7.1f}")
    print(f"  {'Ahmedabad (23.0N)':<22}{'this study':<18}"
          f"{'--':>8}{'--':>7}{'--':>8}{0.15:>7.2f}")
    print("\n  Pausa spans roughly mid-December to mid-January, straddling")
    print("  the December minimum and the January rise, so the effective")
    print("  Pausa base rate at a good site is ~2-3 wet days.")

    pd.DataFrame(belt, columns=["station", "source", "dec_mm", "dec_days",
                                "jan_mm", "jan_days"]).to_csv(
        K.OUT / "belt_normals.csv", index=False)


if __name__ == "__main__":
    main()
