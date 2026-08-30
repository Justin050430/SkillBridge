# Section 1 – Introduction to the System

**Module:** Assessment & Progress Management
**Member:** OOI JIAN XIN
**System:** SkillBridge

---

## 1.1 Overview of SkillBridge

SkillBridge is a web-based e-learning platform developed for a hypothetical
education-focused organisation whose mission is to make quality education
accessible to underserved communities. The system is built with **PHP** and
**MySQL** using the **Laravel 12** framework, and follows the
**Model-View-Controller (MVC)** architecture with the **Eloquent ORM** used for
all database interaction.

SkillBridge brings together a set of integrated modules to form a complete
learning journey:

- **User Management** – user accounts, roles and authentication;
- **Course Management** – courses and lessons created by mentors;
- **Enrollment Management** – students enrolling in and dropping courses;
- **Assessment & Progress Management** (the module covered in this report) –
  assessments, automatic grading, results and progress tracking;
- **Notification** – in-app notifications for students, mentors and admins.

## 1.2 Chosen SDG – Goal 4: Quality Education

SkillBridge addresses **United Nations Sustainable Development Goal 4:
Quality Education**, which aims to *"ensure inclusive and equitable quality
education and promote lifelong learning opportunities for all"* (United
Nations, n.d.).

Many learners in underserved communities face barriers such as the cost of
formal education, a lack of qualified teachers and limited access to learning
materials. SkillBridge contributes to SDG 4 by providing an affordable,
structured online learning platform where mentors can publish courses broken
into lessons with assessments, and where students can learn at their own pace,
receive immediate automated feedback and track their own progress. This
directly supports the goal of widening access to quality education.

The target users are:

- **Students** – learners who enroll in courses, study lessons and take
  assessments;
- **Mentors** – subject experts who create and manage courses, lessons and
  assessments;
- **Administrators** – platform managers who oversee users, courses,
  enrollments and overall platform analytics.

## 1.3 Contribution of the Assessment & Progress Management Module

Within SkillBridge, the **Assessment & Progress Management** module is
responsible for how students are assessed and how their learning progress is
measured. Specifically, the module provides:

- management of **assessments** and **questions** (multiple-choice, true/false
  and short-answer) by admins and mentors;
- the **student assessment flow** – taking an assessment, automatic grading,
  attempt limits and detailed result feedback;
- **progress tracking** – lessons are automatically marked as completed when
  the student passes the lesson's assessment;
- **administrative reports** that aggregate platform content and learner
  performance;
- an **assessment analytics web service** that the module exposes (and the
  consumption of the Course module's catalogue web service), following the
  assignment's Interface Agreement (IFA).

The remainder of this report describes this module in detail, including its
entities, the Observer design pattern used, the secure coding practices
applied, and the web services exposed and consumed.

---
*Note for the report:* replace the source citation for SDG 4 with the APA 7
reference list entry in Section 7 (United Nations, n.d.). The
References section will contain the full APA 7 citations.
