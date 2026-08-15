"""
01_arithmetic.py -- verify the arithmetic reconstruction of vv. 30-33.

Establishes that the nesting is exact at every level with no remainder,
and that the observation->forecast mapping is a bijection at one observed
hour per half-day forecast. Reads no data.

Paper: section 2.2, Figure 1.
"""
import kp_common as K


def main():
    K.banner("KRSI-PARASARA vv. 30-33 -- ARITHMETIC RECONSTRUCTION")

    print("Units (standard Indian horology)")
    print(f"  1 danda (= ghatika)      = {K.DANDA_MIN} min")
    print(f"  1 civil day              = {K.DANDAS_PER_DAY} dandas = 24 h")

    print("\nv.30  sardham dinadvayam manam  (the measure of two and a half days)")
    print(f"  {K.OBS_DAYS} days = {K.OBS_DAYS} x {K.DANDAS_PER_DAY} "
          f"= {K.OBS_DANDAS:.0f} dandas")
    print(f"        = {K.OBS_DANDAS:.0f} x {K.DANDA_MIN} min "
          f"= {K.OBS_DANDAS * K.DANDA_MIN:.0f} min = {K.OBS_HOURS} hours")

    print("\nv.32a ekaikam pancadandena masasya divaso matah")
    print("      (each single unit of five dandas is one day of the month)")
    print(f"  {K.UNIT_DANDAS} dandas = {K.UNIT_DANDAS * K.DANDA_MIN} min "
          f"= {K.UNIT_HOURS:.0f} hours")
    print(f"  {K.OBS_DANDAS:.0f} / {K.UNIT_DANDAS} = {K.N_FORECAST_DAY} units")

    ok = K.N_FORECAST_DAY == 30
    print(f"  -> {K.N_FORECAST_DAY} units = 30 days of the month   "
          f"[{'EXACT' if ok else 'MISMATCH'}]")
    assert ok, "unit count must be 30"
    assert K.OBS_DANDAS % K.UNIT_DANDAS == 0, "must divide without remainder"

    print("\nv.32b purvardham vasari vrstir uttarardhe ca naisiki")
    print("      (the first half is daytime rain, the second nighttime)")
    print(f"  each unit halves at {K.HALF_UNIT_DANDAS} dandas "
          f"= {K.HALF_UNIT_DANDAS * K.DANDA_MIN:.0f} min = 1 hour")
    print(f"    purvardha  (hour 2k)     -> vasari vrsti, day k")
    print(f"    uttarardha (hour 2k + 1) -> naisiki,      night of day k")

    K.banner("THE BIJECTION")
    obs_h = K.OBS_HOURS
    fc_halves = K.N_FORECAST_DAY * 2
    print(f"  observed hours         = {obs_h}")
    print(f"  half-day forecasts     = {K.N_FORECAST_DAY} days x 2 = {fc_halves}")
    print(f"  ratio                  = {obs_h / fc_halves:.1f} h per half-day")
    assert obs_h == fc_halves, "mapping must be one-to-one"
    print("  -> bijection confirmed: 1 observed hour = 1 half-day forecast")

    K.banner("SCOPE")
    print("  Section heading: PAUSA-VRSTI-NIRNAYA -- determination of the")
    print("  rain OF PAUSA. The wind is the instrument, not the subject.")
    print("  masiki vrsti (vv. 30, 33) = the rain of the month in question.")
    print("  pausadina  = where the observation is taken, not an extension")
    print("               of the forecast beyond the month observed.")
    print("\n  One observation, in Pausa, for Pausa's own 30 days.")
    print("  A single annual operation of 60 hours.")

    K.banner("SUPERSEDED READINGS (recorded, appendix C)")
    print("  (ii)  one continuous 30-day vigil, 12 blocks of 2.5 d encoding")
    print("        the 12 months of the following year.")
    print(f"        12 x {K.OBS_DAYS} = {12 * K.OBS_DAYS:.0f} days of observation")
    print("        -- arithmetically seductive, but requires a month-long")
    print("           uninterrupted watch and finds no warrant in the text.")
    print("  (iii) the procedure recurring at every month's opening.")
    print("        -- practicable, but over-generalises pausadina and")
    print("           ignores the section heading.")
    print("\n  Both were implemented and tested; see 06_superseded.py.")


if __name__ == "__main__":
    main()
