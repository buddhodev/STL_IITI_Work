"""
07_figures.py -- generate every figure in the paper as vector PDF.

  f1_mapping.pdf     the Pausa-only mapping (vv. 30, 32)
  f2_compass.pdf     the verse-31 directional key
  f3_degeneracy.pdf  observed vs predicted; base-rate comparison
  f4_power.pdf       power curve
  f5_wd.pdf          western disturbance vs verse 31
  f6_provenance.pdf  provenance vs test site; winter wind regime

Writes to output/figures/.
"""
import numpy as np
import pandas as pd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import Rectangle, Ellipse
import kp_common as K

plt.rcParams.update({"font.family": "serif", "font.size": 9})
FIG = K.OUT / "figures"
FIG.mkdir(exist_ok=True)

NAVY, BRICK, SEA, SKT = "#1a3a5c", "#b2182b", "#2166ac", "#7b3f00"


def fig1_mapping():
    fig, ax = plt.subplots(figsize=(9.5, 3.9))
    ax.axis("off"); ax.set_xlim(0, 100); ax.set_ylim(0, 40)
    ax.text(50, 37.5, "Observation: 2.5 days (150 dandas = 60 h) from "
            "Pausa sukla pratipada sunrise",
            ha="center", fontsize=9.5, weight="bold")
    ax.add_patch(Rectangle((34, 30), 32, 4.4, facecolor=SKT, alpha=.75))
    ax.text(50, 32.2, "2.5 days observed", ha="center", va="center",
            fontsize=8.5, color="w", weight="bold")
    ax.annotate("", xy=(50, 27.5), xytext=(50, 29.6),
                arrowprops=dict(arrowstyle="-|>", color="#333", lw=1.6))
    ax.text(50, 25.6, "forecasts the 30 days of Pausa itself "
            "-- and nothing beyond", ha="center", fontsize=8.6, style="italic")
    for d in range(30):
        x = 8 + d * 2.82
        ax.add_patch(Rectangle((x, 16), 1.28, 5, facecolor="#4a7ba7",
                               ec="w", lw=.4))
        ax.add_patch(Rectangle((x + 1.33, 16), 1.28, 5, facecolor="#1f3d5c",
                               ec="w", lw=.4))
        if d % 5 == 0 or d == 29:
            ax.text(x + 1.33, 14.2, str(d + 1), ha="center", fontsize=5.6,
                    color="#555")
    ax.text(50, 22.4, "30 units of 5 dandas (2 h), one per day of Pausa "
            "-- verse 32", ha="center", fontsize=7.8)
    ax.add_patch(Rectangle((25, 7.4), 3, 2.5, facecolor="#4a7ba7"))
    ax.text(29.6, 8.65, "first 2.5 dandas (1 h) -> vasari vrsti, that day",
            va="center", fontsize=7.6)
    ax.add_patch(Rectangle((25, 3.6), 3, 2.5, facecolor="#1f3d5c"))
    ax.text(29.6, 4.85, "second 2.5 dandas (1 h) -> naisiki, that night",
            va="center", fontsize=7.6)
    ax.text(50, .7, "60 hours observed  <->  60 half-day forecasts",
            ha="center", fontsize=8, weight="bold", color=SKT)
    fig.savefig(FIG / "f1_mapping.pdf", bbox_inches="tight")
    plt.close(fig)


def fig2_compass():
    fig, ax = plt.subplots(figsize=(4.4, 4.4), subplot_kw={"projection": "polar"})
    ax.set_theta_zero_location("N"); ax.set_theta_direction(-1)
    segs = [(315, 45, "#2166ac", "saumya (N)\nVRSTI"),
            (225, 315, "#4393c3", "varuna (W)\nVRSTI"),
            (45, 135, "#d6604d", "purva (E)\nAVRSTI"),
            (135, 225, "#b2182b", "yamya (S)\nAVRSTI")]
    for a, b, c, lab in segs:
        hi = b if b > a else b + 360
        th = np.deg2rad(np.linspace(a, hi, 60))
        ax.fill_between(th, 0, 1, color=c, alpha=.85)
        ax.text(np.deg2rad(((a + hi) / 2) % 360), .58, lab, ha="center",
                va="center", fontsize=7.5, color="w", weight="bold")
    ax.set_ylim(0, 1); ax.set_yticks([])
    ax.set_xticks(np.deg2rad([0, 90, 180, 270]))
    ax.set_xticklabels(["N", "E", "S", "W"], fontsize=10, weight="bold")
    ax.grid(alpha=.25)
    fig.savefig(FIG / "f2_compass.pdf", bbox_inches="tight")
    plt.close(fig)


def fig3_degeneracy():
    S = pd.read_csv(K.OUT / "pausa_season_summary.csv")
    B = pd.read_csv(K.OUT / "belt_normals.csv")
    fig, (ax, bx) = plt.subplots(1, 2, figsize=(11, 3.5),
                                 gridspec_kw={"width_ratios": [1.25, 1]})
    cols = [BRICK if v == 0 else SEA for v in S.rain_mm]
    ax.bar(S.season.astype(str), S.rain_mm, color=cols, ec="w")
    a2 = ax.twinx()
    a2.plot(range(len(S)), S.pred_frac, "o--", color=SKT, lw=1.3, ms=4.5)
    a2.set_ylim(0, 1.05)
    a2.set_ylabel("predicted rainy fraction", color=SKT, fontsize=8.5)
    ax.set_ylabel("Pausa rainfall total (mm)"); ax.set_xlabel("Pausa season")
    ax.tick_params(axis="x", rotation=45, labelsize=7.5)
    ax.set_title("(a) Observed Pausa rainfall vs. prediction, 13 seasons",
                 fontsize=9.5)
    nzero = int((S.rain_mm == 0).sum())
    ax.text(.5, .93, f"{nzero} of {len(S)} seasons: exactly 0.0 mm",
            transform=ax.transAxes, ha="center", fontsize=8,
            bbox=dict(boxstyle="round,pad=.4", fc="#fdecea", ec=BRICK))
    ax.spines[["top"]].set_visible(False)

    lab = ["Ahmedabad\n(this study)"] + [s.split(" (")[0] for s in B.station
                                         if "Ahmedabad" not in s]
    val = [0.15] + list(B.jan_days)
    lab, val = lab[:7], val[:7]
    order = np.argsort(val)
    lab = [lab[i] for i in order]; val = [val[i] for i in order]
    cols = [BRICK if "Ahmedabad" in s else
            (SEA if v >= 3 else "#9ecae1") for s, v in zip(lab, val)]
    bb = bx.barh(lab, val, color=cols, ec="w")
    for r, v in zip(bb, val):
        bx.text(v + .08, r.get_y() + r.get_height() / 2, f"{v}",
                va="center", fontsize=8)
    bx.set_xlabel("mean January rainy days")
    bx.set_title("(b) Winter rainy-day base rate", fontsize=9.5)
    bx.tick_params(labelsize=7.4); bx.set_xlim(0, max(val) * 1.25)
    bx.spines[["top", "right"]].set_visible(False)
    fig.tight_layout()
    fig.savefig(FIG / "f3_degeneracy.pdf", bbox_inches="tight")
    plt.close(fig)


def fig4_power():
    P = pd.read_csv(K.OUT / "power_curve.csv").dropna(subset=["power"])
    fig, ax = plt.subplots(figsize=(5.8, 3.2))
    ax.plot(P.relative_risk, P.power * 100, "o-", color=BRICK, lw=1.9, ms=7)
    ax.axhline(80, color=SEA, ls="--", lw=1.2)
    ax.text(P.relative_risk.min() * 1.05, 83, "conventional 80% power",
            fontsize=7.6, color=SEA)
    for x, y in zip(P.relative_risk, P.power * 100):
        ax.annotate(f"{y:.1f}%", (x, y), textcoords="offset points",
                    xytext=(6, 5), fontsize=7.6)
    ax.set_xscale("log")
    ax.set_xticks(list(P.relative_risk))
    ax.set_xticklabels([f"{int(v)}x" for v in P.relative_risk])
    ax.set_xlabel("true relative risk on predicted-rain days")
    ax.set_ylabel("power at alpha = 0.05 (%)")
    ax.set_title("Power at Ahmedabad: 2 events in 390 days", fontsize=9.5)
    ax.set_ylim(0, 100)
    ax.spines[["top", "right"]].set_visible(False)
    fig.tight_layout()
    fig.savefig(FIG / "f4_power.pdf", bbox_inches="tight")
    plt.close(fig)


def fig5_wd():
    fig, ax = plt.subplots(figsize=(9, 3.4))
    ax.axis("off"); ax.set_xlim(0, 100); ax.set_ylim(0, 34)
    ax.add_patch(Ellipse((50, 19), 34, 13, facecolor="#c6dbef", alpha=.75,
                         ec="#4292c6", lw=1.4))
    ax.text(50, 25.5, "Western Disturbance", ha="center", fontsize=9,
            weight="bold", color="#1a4e78")
    ax.annotate("", xy=(78, 19), xytext=(62, 19),
                arrowprops=dict(arrowstyle="-|>", color="#1a4e78", lw=2))
    ax.text(70, 21, "track E", ha="center", fontsize=7, color="#1a4e78")
    for i in range(7):
        ax.plot([40 + i * 3.2, 39 + i * 3.2], [15.5, 11.5],
                color="#4292c6", lw=1.1)
    ax.text(50, 8.6, "precipitation", ha="center", fontsize=7.5,
            color="#1a4e78", style="italic")
    ax.annotate("", xy=(30, 19), xytext=(12, 19),
                arrowprops=dict(arrowstyle="-|>", color=SEA, lw=2.6))
    ax.text(20, 22.4, "AHEAD: warm, moist\nWESTERLY / SW", ha="center",
            fontsize=8, color=SEA, weight="bold")
    ax.text(20, 13.6, "verse 31 varuna -> vrsti\nCORROBORATED", ha="center",
            fontsize=7.6, color="#1a7040")
    ax.annotate("", xy=(88, 26), xytext=(74, 31),
                arrowprops=dict(arrowstyle="-|>", color=BRICK, lw=2.6))
    ax.text(89, 29.5, "BEHIND: cold, dry\nNORTHERLY / NW", ha="left",
            fontsize=8, color=BRICK, weight="bold")
    ax.text(89, 21, "verse 31 saumya -> vrsti\nINVERTED (clearance)",
            ha="left", fontsize=7.6, color=BRICK)
    ax.text(50, 2.2, "Dimri et al. (2015): a migrating WD is preceded by "
            "warm, moist air and followed by cold, dry air",
            ha="center", fontsize=7.6, style="italic", color="#444")
    fig.savefig(FIG / "f5_wd.pdf", bbox_inches="tight")
    plt.close(fig)


def fig6_provenance():
    hourly = K.load_hourly()
    hourly["sector"] = [K.sector(t) for t in hourly.winddirdegree]
    djf = hourly[hourly.date.dt.month.isin([12, 1, 2])]
    freq = djf.sector.value_counts(normalize=True) * 100

    fig, (ax, bx) = plt.subplots(1, 2, figsize=(11, 4.6),
                                 gridspec_kw={"width_ratios": [1.15, 1]})
    ax.add_patch(Rectangle((68, 30), 12, 4, facecolor=SEA, alpha=.22,
                           ec=SEA, lw=1.3))
    ax.text(74, 34.6, "Observational core\n30-34 N", ha="center",
            fontsize=8, color="#12456e", weight="bold")
    ax.plot(74.52, 32.50, "o", ms=7, color="#12456e")
    ax.text(75.3, 32.5, "Sialkot", fontsize=8, va="center")
    ax.plot(74.87, 32.73, "o", ms=5, color="#12456e", mfc="w")
    ax.text(75.6, 33.9, "Jammu", fontsize=7.5, va="center")
    ax.add_patch(Ellipse((88.3, 23.4), 5.2, 4.2, facecolor=SKT, alpha=.2,
                         ec=SKT, lw=1.3))
    ax.text(88.3, 26.2, "Redaction &\ntransmission", ha="center",
            fontsize=8, color="#5a2e00", weight="bold")
    ax.plot(88.36, 22.57, "s", ms=6, color="#5a2e00")
    ax.text(89.2, 22.4, "Bengal", fontsize=8, va="center")
    ax.plot(72.58, 23.03, "*", ms=19, color=BRICK)
    ax.text(71.6, 21.6, "Ahmedabad\n(this test)", ha="center", fontsize=8.5,
            color=BRICK, weight="bold")
    ax.annotate("", xy=(72.9, 22.4), xytext=(73.6, 30.2),
                arrowprops=dict(arrowstyle="<->", color=BRICK, lw=1.4, ls="--"))
    ax.text(74.4, 26.3, "7.5 deg latitude\ndisplacement", fontsize=7.6,
            color=BRICK, style="italic")
    ax.set_xlim(66, 94); ax.set_ylim(18, 37)
    ax.set_xlabel("Longitude (E)"); ax.set_ylabel("Latitude (N)")
    ax.set_title("(a) Provenance of the Krsi-Parasara vs. test site",
                 fontsize=9.5)
    ax.grid(alpha=.22); ax.spines[["top", "right"]].set_visible(False)

    secs = ["purva", "saumya", "varuna", "yamya"]
    labs = ["E", "N", "W", "S"]
    vals = [freq.get(s, 0) for s in secs]
    cols = ["#d6604d", SEA, "#4393c3", BRICK]
    b = bx.bar(labs, vals, color=cols, ec="w")
    bx.axhline(25, color="#888", ls=":", lw=1)
    bx.set_ylabel("% of DJF hours")
    bx.set_xlabel("wind sector (source direction)")
    bx.set_title("(b) Ahmedabad winter wind regime", fontsize=9.5)
    for r, v in zip(b, vals):
        bx.text(r.get_x() + r.get_width() / 2, v + 1, f"{v:.1f}%",
                ha="center", fontsize=8)
    nw = djf.sector.isin(["saumya", "varuna"]).mean() * 100
    bx.text(.5, .80, f"KP rain sectors (N+W): {nw:.1f}%\n"
            f"KP drought sectors (E+S): {100 - nw:.1f}%\n\n"
            "DJF rainfall: 2.9 mm/season\nWet days: 0.9%",
            transform=bx.transAxes, fontsize=7.8, va="top", ha="center",
            bbox=dict(boxstyle="round,pad=.5", fc="#f4f4f4", ec="#bbb"))
    bx.set_ylim(0, 52); bx.spines[["top", "right"]].set_visible(False)
    fig.tight_layout()
    fig.savefig(FIG / "f6_provenance.pdf", bbox_inches="tight")
    plt.close(fig)


def main():
    K.banner("GENERATING FIGURES")
    for fn in [fig1_mapping, fig2_compass, fig3_degeneracy,
               fig4_power, fig5_wd, fig6_provenance]:
        fn()
        print(f"  {fn.__name__}")
    print(f"\nwrote {FIG}")


if __name__ == "__main__":
    main()
