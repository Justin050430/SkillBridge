# -*- coding: utf-8 -*-
"""
Generate the two UML diagrams for OOI JIAN XIN's individual report:

  1. entity-class-diagram.png   - Section 3 (Entity Classes)
  2. observer-class-diagram.png - Section 4 (Observer Design Pattern)

Run:  python make_diagrams.py
"""
import os

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import Rectangle

OUT = os.path.dirname(os.path.abspath(__file__))
INK = "#1f2937"
HEADER = "#e6ecf7"
IFACE = "#f3e8ff"
BODY = "#ffffff"


def classbox(ax, cx, cy, w, lines, name, is_interface=False, header_color=HEADER,
             center_body=False):
    """Draw a UML class box centred at (cx, cy). `lines` are body entries."""
    line_h = 0.36
    header_h = 0.5
    pad = 0.26
    h = header_h + pad + max(len(lines), 0) * line_h
    x0, y0 = cx - w / 2.0, cy - h / 2.0

    ax.add_patch(Rectangle((x0, y0), w, h, facecolor=BODY, edgecolor=INK, lw=1.5, zorder=3))
    ax.add_patch(Rectangle((x0, y0 + h - header_h), w, header_h,
                           facecolor=header_color, edgecolor=INK, lw=1.2, zorder=3))

    title = ("\u00abinterface\u00bb\n" if is_interface else "") + name
    ax.text(cx, y0 + h - header_h / 2.0, title, ha="center", va="center",
            fontsize=11.5, fontweight="bold", zorder=4)

    yy = y0 + h - header_h - pad + line_h * 0.12
    for ln in lines:
        ha = "center" if center_body else "left"
        x = cx if center_body else x0 + 0.18
        ax.text(x, yy, ln, ha=ha, va="center", fontsize=9.5, zorder=4,
                family="DejaVu Sans")
        yy -= line_h
    return (x0, y0, w, h)


def seg_params(p1, p2):
    import math
    dx, dy = p2[0] - p1[0], p2[1] - p1[1]
    length = math.hypot(dx, dy) or 1.0
    return dx / length, dy / length


def rel(ax, p1, p2, mult1="", mult2="", label="", dashed=False, arrow=False,
        lw=1.4, label_dy=0.16, mult_dy=0.13):
    """Draw an association/relationship between two box-edge points."""
    style = "--" if dashed else "-"
    if arrow:
        ax.annotate("", xy=p2, xytext=p1,
                    arrowprops=dict(arrowstyle="-|>", color=INK, lw=lw,
                                    linestyle=style, shrinkA=0, shrinkB=0),
                    zorder=2)
    else:
        ax.plot([p1[0], p2[0]], [p1[1], p2[1]], color=INK, lw=lw, linestyle=style, zorder=2)

    ux, uy = seg_params(p1, p2)
    # perpendicular offset for labels
    px, py = -uy, ux
    if mult1:
        x, y = p1[0] + (p2[0] - p1[0]) * 0.10 + px * mult_dy, p1[1] + (p2[1] - p1[1]) * 0.10 + py * mult_dy
        ax.text(x, y, mult1, ha="center", va="center", fontsize=9.5, fontweight="bold", zorder=5)
    if mult2:
        x, y = p2[0] - (p2[0] - p1[0]) * 0.10 + px * mult_dy, p2[1] - (p2[1] - p1[1]) * 0.10 + py * mult_dy
        ax.text(x, y, mult2, ha="center", va="center", fontsize=9.5, fontweight="bold", zorder=5)
    if label:
        mx, my = (p1[0] + p2[0]) / 2.0, (p1[1] + p2[1]) / 2.0
        ax.text(mx, my + label_dy, label, ha="center", va="bottom",
                fontsize=8.5, style="italic", color="#334155", zorder=5,
                bbox=dict(boxstyle="round,pad=0.12", fc="white", ec="none", alpha=0.85))


def frame(ax, xlim, ylim, title):
    ax.set_xlim(*xlim)
    ax.set_ylim(*ylim)
    ax.axis("off")
    ax.set_aspect("equal")
    ax.set_title(title, fontsize=13, fontweight="bold", pad=12)


# --------------------------------------------------------------------------
# Diagram 1 - Entity Class Diagram (Section 3)
# --------------------------------------------------------------------------
def entity_diagram():
    fig, ax = plt.subplots(figsize=(13, 9.5), dpi=170)
    W = 4.6

    lesson = classbox(ax, 3.3, 9.6, W, ["title", "order_number", "status",
                                         "assessments()", "progresses()"], "Lesson")
    enrollment = classbox(ax, 14.6, 9.6, W, ["status", "enrolled_at", "completed_at",
                                              "assessmentAttempts()", "progress()"], "Enrollment")
    progress = classbox(ax, 8.95, 9.6, W, ["status", "completed_at",
                                           "enrollment()", "lesson()"], "Progress")

    assessment = classbox(ax, 3.3, 4.7, W, ["title", "passing_score", "max_attempts", "status",
                                             "lesson()", "questions()", "attempts()"], "Assessment")
    attempt = classbox(ax, 14.6, 4.7, W, ["attempt_number", "score", "percentage", "is_passed",
                                          "enrollment()", "assessment()", "studentAnswers()"], "AssessmentAttempt")

    question = classbox(ax, 3.3, -0.4, W, ["question_type", "question_text", "marks",
                                           "assessment()", "studentAnswers()"], "Question")
    answer = classbox(ax, 8.95, -0.4, W, ["submitted_answer", "is_correct", "marks_awarded",
                                          "attempt()", "question()"], "StudentAnswer")

    def edge_top(b): return (b[0] + b[2] / 2.0, b[1] + b[3])
    def edge_bot(b): return (b[0] + b[2] / 2.0, b[1])
    def edge_left(b, f=0.5): return (b[0], b[1] + b[3] * f)
    def edge_right(b, f=0.5): return (b[0] + b[2], b[1] + b[3] * f)

    # Lesson -> Assessment (1..*)
    rel(ax, edge_bot(lesson), edge_top(assessment), mult1="1", mult2="0..*", label="has many assessments")
    # Lesson -> Progress and Enrollment -> Progress (1..*)
    rel(ax, edge_right(lesson, 0.62), edge_left(progress, 0.62), mult1="1", mult2="0..*", label="lesson progress")
    rel(ax, edge_left(enrollment, 0.62), edge_right(progress, 0.62), mult1="1", mult2="0..*", label="enrolment progress")
    # Enrollment -> AssessmentAttempt (1..*)
    rel(ax, edge_bot(enrollment), edge_top(attempt), mult1="1", mult2="0..*", label="has many attempts")
    # Assessment -> AssessmentAttempt (1..*)
    rel(ax, edge_right(assessment, 0.55), edge_left(attempt, 0.55), mult1="1", mult2="0..*", label="attempts for")
    # Assessment -> Question (1..*)
    rel(ax, edge_bot(assessment), edge_top(question), mult1="1", mult2="0..*", label="contains questions")
    # Question -> StudentAnswer (1..*)
    rel(ax, edge_right(question, 0.55), edge_left(answer, 0.55), mult1="1", mult2="0..*", label="answered by")
    # AssessmentAttempt -> StudentAnswer (1..*)
    rel(ax, (attempt[0] + attempt[2] * 0.62, attempt[1]), edge_right(answer, 0.62),
        mult1="1", mult2="0..*", label="records answers")

    frame(ax, (0, 18), (-1.6, 12.6),
          "Entity Class Diagram - Assessment & Progress Management module")
    fig.tight_layout()
    fig.savefig(os.path.join(OUT, "entity-class-diagram.png"), bbox_inches="tight",
                facecolor="white")
    plt.close(fig)


# --------------------------------------------------------------------------
# Diagram 2 - Observer class diagram (Section 4)
# --------------------------------------------------------------------------
def observer_diagram():
    fig, ax = plt.subplots(figsize=(14, 10), dpi=170)

    subject = classbox(ax, 3.2, 11.2, 5.2,
                       ["attach(Observer)", "detach(Observer)", "notify()"],
                       "Subject", is_interface=True, header_color=IFACE, center_body=True)
    observer = classbox(ax, 15.0, 11.2, 8.8,
                        ["update(Subject)"],
                        "Observer", is_interface=True, header_color=IFACE, center_body=True)

    concrete_subject = classbox(ax, 3.2, 4.6, 5.6,
                                ["- observers : Observer[]",
                                 "+ attach(Observer)", "+ detach(Observer)",
                                 "+ notify()", "+ getAttempt()", "+ getAssessment()"],
                                "AssessmentSubmission")
    p_updater = classbox(ax, 11.2, 2.4, 4.3, ["+ update(Subject)"], "ProgressUpdater")
    p_logger = classbox(ax, 15.0, 2.4, 4.3, ["+ update(Subject)"], "SubmissionLogger")
    n_dispatch = classbox(ax, 18.8, 2.4, 4.3, ["+ update(Subject)"], "NotificationDispatchObserver")

    def edge_top(b): return (b[0] + b[2] / 2.0, b[1] + b[3])
    def edge_bot(b): return (b[0] + b[2] / 2.0, b[1])
    def edge_left(b, f=0.5): return (b[0], b[1] + b[3] * f)
    def edge_right(b, f=0.5): return (b[0] + b[2], b[1] + b[3] * f)

    # ConcreteSubject implements Subject (dashed, arrow points to interface)
    rel(ax, edge_top(concrete_subject), edge_bot(subject), dashed=True, arrow=True,
        label="implements", label_dy=0.12)

    # ConcreteObservers implement Observer
    ob_bot_y = observer[1]
    rel(ax, edge_top(p_updater), (observer[0] + observer[2] * 0.24, ob_bot_y),
        dashed=True, arrow=True, label="implements", label_dy=0.0)
    rel(ax, edge_top(p_logger), (observer[0] + observer[2] * 0.50, ob_bot_y),
        dashed=True, arrow=True, label="implements", label_dy=0.0)
    rel(ax, edge_top(n_dispatch), (observer[0] + observer[2] * 0.78, ob_bot_y),
        dashed=True, arrow=True, label="implements", label_dy=0.0)

    # The subject registers / notifies its observers through the Observer
    # interface (association with multiplicity 0..*).
    rel(ax, edge_right(concrete_subject, 0.80), edge_left(observer, 0.42),
        mult2="0..*", label="holds & notifies observers via attach()/notify()",
        lw=1.2)

    # Observer -> Command integration note
    ax.text(n_dispatch[0] + n_dispatch[2] / 2.0, n_dispatch[1] - 1.0,
            "dispatches notification Commands\nvia NotificationInvoker (Command pattern)",
            ha="center", va="top", fontsize=8.5, style="italic", color="#334155",
            bbox=dict(boxstyle="round,pad=0.35", fc="#eef2ff", ec="#c7d2fe", lw=1.0))

    frame(ax, (-0.6, 23.6), (-2.6, 13.6),
          "Observer Design Pattern class diagram - assessment submission")
    fig.tight_layout()
    fig.savefig(os.path.join(OUT, "observer-class-diagram.png"), bbox_inches="tight",
                facecolor="white")
    plt.close(fig)


def namebox(ax, cx, cy, w=4.8, h=1.8, name="", header_color=HEADER):
    """Draw a compact UML class box showing only the entity name."""
    x0, y0 = cx - w / 2.0, cy - h / 2.0
    ax.add_patch(Rectangle((x0, y0), w, h, facecolor=BODY, edgecolor=INK, lw=1.5, zorder=3))
    ax.add_patch(Rectangle((x0, y0), w, h, facecolor=header_color, edgecolor=INK, lw=1.5, zorder=3))
    ax.text(cx, cy, name, ha="center", va="center", fontsize=10.5, fontweight="bold", zorder=4)
    return (x0, y0, w, h)


# --------------------------------------------------------------------------
# Diagram 3 - Full-system entity (analysis) class diagram
# --------------------------------------------------------------------------
def system_diagram():
    fig, ax = plt.subplots(figsize=(16, 12), dpi=170)
    W, H = 5.0, 1.9
    PALE = "#eaf7ea"      # User management
    COURSE = "#eaf1fb"    # Course management
    ENROL = "#fdf3e7"     # Enrollment management
    ASSESS = "#fbeaf6"    # Assessment & progress
    NOTIF = "#f1edfb"     # Notification

    boxes = {}

    def B(name, cx, cy, color):
        boxes[name] = namebox(ax, cx, cy, W, H, name, color)

    # ---- Account / user management (left) ----
    B("Role", 6, 31, PALE)
    B("User", 6, 24, PALE)
    B("Notification", 17, 31, NOTIF)

    # ---- Course management (centre-right column) ----
    B("Category", 30, 31, COURSE)
    B("Course", 30, 24, COURSE)
    B("Lesson", 30, 17, COURSE)
    B("LessonResource", 39.5, 17, COURSE)

    # ---- Assessment & progress (right column under Lesson) ----
    B("Assessment", 30, 10, ASSESS)
    B("Question", 30, 3, ASSESS)
    B("StudentAnswer", 30, -4, ASSESS)

    # ---- Enrollment management / progress (centre-left) ----
    B("Enrollment", 17, 17, ENROL)
    B("Progress", 9.5, 10, ENROL)
    B("AssessmentAttempt", 20.5, 3, ASSESS)

    def edge_top(b): return (b[0] + b[2] / 2.0, b[1] + b[3])
    def edge_bot(b): return (b[0] + b[2] / 2.0, b[1])
    def edge_left(b, f=0.5): return (b[0], b[1] + b[3] * f)
    def edge_right(b, f=0.5): return (b[0] + b[2], b[1] + b[3] * f)

    # User management
    rel(ax, edge_bot(boxes["Role"]), edge_top(boxes["User"]), "1", "0..*", "users")
    rel(ax, edge_bot(boxes["Notification"]), edge_right(boxes["User"], 0.7), "1", "0..*", "notifications")
    rel(ax, edge_right(boxes["User"], 0.5), edge_left(boxes["Course"], 0.5), "1", "0..*", "creates / mentors")
    rel(ax, edge_right(boxes["User"], 0.2), edge_left(boxes["Enrollment"], 0.85), "1", "0..*", "student enrolments")

    # Course management
    rel(ax, edge_bot(boxes["Category"]), edge_top(boxes["Course"]), "1", "0..*", "courses")
    rel(ax, edge_bot(boxes["Course"]), edge_top(boxes["Lesson"]), "1", "0..*", "lessons")
    rel(ax, edge_left(boxes["Course"], 0.35), edge_right(boxes["Enrollment"], 0.65), "1", "0..*", "enrolments")
    rel(ax, edge_right(boxes["Lesson"], 0.5), edge_left(boxes["LessonResource"], 0.5), "1", "0..*", "resources")

    # Lesson -> Assessment -> Question -> StudentAnswer
    rel(ax, edge_bot(boxes["Lesson"]), edge_top(boxes["Assessment"]), "1", "0..*", "assesses")
    rel(ax, edge_bot(boxes["Assessment"]), edge_top(boxes["Question"]), "1", "0..*", "contains questions")
    rel(ax, edge_bot(boxes["Question"]), edge_top(boxes["StudentAnswer"]), "1", "0..*", "answers given")

    # Progress (lesson + enrollment)
    rel(ax, edge_left(boxes["Lesson"], 0.18), edge_right(boxes["Progress"], 0.5), "1", "0..*", "lesson progress")
    rel(ax, edge_left(boxes["Enrollment"], 0.3), edge_right(boxes["Progress"], 0.7), "1", "0..*", "enrolment progress")

    # Attempts (enrollment + assessment) -> StudentAnswer
    rel(ax, edge_bot(boxes["Enrollment"]), edge_top(boxes["AssessmentAttempt"]), "1", "0..*", "attempts")
    rel(ax, edge_left(boxes["Assessment"], 0.35), edge_top(boxes["AssessmentAttempt"]), "1", "0..*", "attempts for")
    rel(ax, edge_right(boxes["AssessmentAttempt"], 0.5), edge_left(boxes["StudentAnswer"], 0.5), "1", "0..*", "answers given")

    # module legend
    legend_items = [
        ("User Management", PALE), ("Course Management", COURSE),
        ("Enrollment Management", ENROL), ("Assessment & Progress", ASSESS),
        ("Notification", NOTIF),
    ]
    lx = 1.0
    ly = -6.6
    for label, color in legend_items:
        ax.add_patch(Rectangle((lx, ly), 1.2, 0.7, facecolor=color, edgecolor=INK, lw=0.8))
        ax.text(lx + 1.5, ly + 0.35, label, ha="left", va="center", fontsize=9)
        lx += len(label) * 0.18 + 3.6

    frame(ax, (0, 46), (-8.5, 34),
          "System Entity (Analysis) Class Diagram - SkillBridge")
    fig.tight_layout()
    fig.savefig(os.path.join(OUT, "system-entity-class-diagram.png"), bbox_inches="tight",
                facecolor="white")
    plt.close(fig)


if __name__ == "__main__":
    entity_diagram()
    observer_diagram()
    system_diagram()
    print("Diagrams written to", OUT)
