"""
kp_common.py -- shared configuration and primitives for the
Krsi-Parasara Pausa-vrsti-nirnaya analysis (vv. 30-33).

Buddhodev Ghosh, from data collected by Damodara Das.

All other scripts import from this module. Nothing here performs analysis;
it defines the horology, the verse-31 decision rule, the observation->forecast
mapping, and the verification primitives.
"""
import numpy as np
import pandas as pd
from pathlib import Path

# --------------------------------------------------------------------------
# PATHS
# --------------------------------------------------------------------------
DATA = Path("/mnt/user-data/uploads")
OUT = Path(__file__).resolve().parent.parent / "output"
OUT.mkdir(exist_ok=True)

HOURLY = DATA / "weather_data_1hr.csv"
DAILY = DATA / "weather_data_24hr.csv"

# --------------------------------------------------------------------------
# HOROLOGY  (Krsi-Parasara vv. 30, 32)
#
#   v.30  sardham dinadvayam manam  -> 2.5 days of observation
#   v.32  ekaikam pancadandena masasya divaso matah
#             -> each 5 dandas = one day of the month
#         purvardham vasari vrstir uttarardhe ca naisiki
#             -> first half of the unit = that day's daytime rain
#                second half            = that same day's night rain
# --------------------------------------------------------------------------
DANDA_MIN = 24                                     # 1 danda = 1 ghatika = 24 min
DANDAS_PER_DAY = 60                                # 1 civil day = 60 dandas
OBS_DAYS = 2.5
OBS_DANDAS = OBS_DAYS * DANDAS_PER_DAY             # = 150
OBS_HOURS = int(OBS_DANDAS * DANDA_MIN / 60)       # = 60
UNIT_DANDAS = 5
UNIT_HOURS = UNIT_DANDAS * DANDA_MIN / 60          # = 2
N_FORECAST_DAY = int(OBS_DANDAS / UNIT_DANDAS)     # = 30
HALF_UNIT_DANDAS = UNIT_DANDAS / 2                 # = 2.5 dandas = 1 hour

SYNODIC = 29.530588                                # mean synodic month, days

# --------------------------------------------------------------------------
# SITE
# --------------------------------------------------------------------------
SITE = "Ahmedabad"
LAT, LON = 23.03, 72.58
SUNRISE_H = 7          # Ahmedabad Dec sunrise 07:13-07:22 IST; 07:00 adopted

# Pausa sukla pratipada, Lahiri pancanga, Gujarat longitude
PAUSA_START = {
    2008: "2008-12-28", 2009: "2009-12-16", 2010: "2010-12-05",
    2011: "2011-12-24", 2012: "2012-12-13", 2013: "2013-12-02",
    2014: "2014-12-22", 2015: "2015-12-11", 2016: "2016-12-29",
    2017: "2017-12-18", 2018: "2018-12-07", 2019: "2019-12-26",
    2020: "2020-12-14",
}

LUNAR_MONTHS = ["Pausa", "Magha", "Phalguna", "Caitra", "Vaisakha",
                "Jyestha", "Asadha", "Sravana", "Bhadrapada", "Asvina",
                "Karttika", "Margasirsa"]

WET_MM = 1.0            # wet-day threshold used throughout
IMD_RAINY_MM = 2.5      # IMD's own definition, for comparison with normals

# --------------------------------------------------------------------------
# VERSE 31 -- the directional rule
#
#   saumya-varunayor vrstir avrstih purva-yamyayoh |
#   nirvate vrstihanih syat samkule samkulam jalam ||
#
#   saumya (N) + varuna (W) -> vrsti   (rain)
#   purva  (E) + yamya  (S) -> avrsti  (no rain)
#   nirvata (calm)          -> vrstihani
#
# theta is the direction the wind blows FROM, degrees true.
# --------------------------------------------------------------------------
CALM_KMPH = 1.0


def sector(theta):
    """Collapse a bearing to the four quarters named in verse 31."""
    d = theta % 360
    if d >= 315 or d < 45:
        return "saumya"     # N
    if d < 135:
        return "purva"      # E
    if d < 225:
        return "yamya"      # S
    return "varuna"         # W


def verse31(theta, speed_kmph, invert=False):
    """1 if verse 31 predicts rain for this hour, else 0.

    invert=True swaps the rain-bearing and drought-bearing sectors; used
    only as a control (script 06) to show the null is an absence of signal
    rather than a reversal of sign.
    """
    if speed_kmph <= CALM_KMPH:
        return 0                                   # nirvata -> vrstihani
    rain = sector(theta) in ("saumya", "varuna")
    return int(not rain) if invert else int(rain)


# --------------------------------------------------------------------------
# LOADERS
# --------------------------------------------------------------------------
def load_hourly():
    """113,232 hourly rows, Ahmedabad, 2008-07-01 to 2021-05-31."""
    df = pd.read_csv(HOURLY)
    df["date"] = pd.to_datetime(df["date"])
    df["hour"] = df["time"] // 100                 # 'time' is HHMM as int
    df["dt"] = df["date"] + pd.to_timedelta(df["hour"], unit="h")
    return df.sort_values("dt").reset_index(drop=True)


def load_daily():
    """4,718 daily rows, same site and period."""
    df = pd.read_csv(DAILY)
    df["date"] = pd.to_datetime(df["date"])
    return df.set_index("date").sort_index()


# --------------------------------------------------------------------------
# THE MAPPING (vv. 30, 32)
# --------------------------------------------------------------------------
def observation_window(hourly, start_date, sunrise_h=SUNRISE_H,
                       offset_days=0, hours=OBS_HOURS):
    """The OBS_HOURS hourly rows beginning at sunrise on the anchor date.

    offset_days perturbs the anchor, for the pancanga sensitivity test.
    """
    t0 = (pd.Timestamp(start_date)
          + pd.Timedelta(days=offset_days, hours=sunrise_h))
    w = hourly[(hourly.dt >= t0)
               & (hourly.dt < t0 + pd.Timedelta(hours=hours))]
    return w.reset_index(drop=True)


def map_to_forecast(window, invert=False):
    """60 observed hours -> 30 days x {day, night}.

    Unit k (5 dandas = 2 h) is day k+1 of the month.
      purvardha  = hour 2k     -> vasari vrsti (that day)
      uttarardha = hour 2k + 1 -> naisiki      (that same night)

    Returns None if the window is short.
    """
    if len(window) < OBS_HOURS:
        return None
    pred = [verse31(a, b, invert) for a, b in
            zip(window.winddirdegree, window.windspeedKmph)]
    return [{"k": k + 1,
             "pred_day": pred[2 * k],
             "pred_night": pred[2 * k + 1],
             "pred_any": max(pred[2 * k], pred[2 * k + 1])}
            for k in range(N_FORECAST_DAY)]


# --------------------------------------------------------------------------
# VERIFICATION
# --------------------------------------------------------------------------
def contingency(pred, obs):
    """2x2 table -> (hits, false_alarms, misses, correct_negatives)."""
    pred = np.asarray(pred).astype(bool)
    obs = np.asarray(obs).astype(bool)
    return (int((pred & obs).sum()), int((pred & ~obs).sum()),
            int((~pred & obs).sum()), int((~pred & ~obs).sum()))


def skill_scores(a, b, c, d):
    """Heidke (HSS) and Peirce (PSS, Hanssen-Kuipers) skill scores."""
    n = a + b + c + d
    out = {"n": n, "accuracy": (a + d) / n if n else np.nan}
    den_h = (a + c) * (c + d) + (a + b) * (b + d)
    out["HSS"] = 2 * (a * d - b * c) / den_h if den_h else np.nan
    den_p = (a + c) * (b + d)
    out["PSS"] = (a * d - b * c) / den_p if den_p else np.nan
    out["base_rate"] = (a + c) / n if n else np.nan
    out["fcst_rate"] = (a + b) / n if n else np.nan
    return out


def n_eff(n, r1, r2=None):
    """Effective sample size for autocorrelated series (Wilks 2011).

    One series:  n_eff = n (1 - r1) / (1 + r1)
    Two series:  n_eff = n (1 - r1 r2) / (1 + r1 r2)
    """
    if r2 is None:
        return n * (1 - r1) / (1 + r1)
    return n * (1 - r1 * r2) / (1 + r1 * r2)


def banner(title):
    print("\n" + "=" * 74)
    print(title)
    print("=" * 74)
