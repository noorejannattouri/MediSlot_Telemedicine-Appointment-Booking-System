# MediSlot_Telemedicine-Appointment-Booking-System
MediSlot is a web platform that lets patients find the right doctor, book a virtual appointment, pay online, and keep every prescription and medical record in one place — while doctors and admins manage the whole process from their own dashboards.

Course: CSE327 — Software Engineering

### The problem:

Getting timely medical care is still harder than it should be:

1.No easy way to check doctor availability before showing up or calling around

2.No single place to search doctors by specialty, location, fee, or rating

3.Prescriptions and visit history live on paper, not with the patient or doctor

4.No booking → video-consult → payment flow that works entirely online

MediSlot solves this with one platform covering search, booking, payment, records, and remote consultation.


## Tech stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, Bootstrap, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Web server | XAMPP |
| AI | Gemini API (Medicine Assistant) |
| Dev tools | VS Code, phpMyAdmin, GitHub |

A classic LAMP-style stack (minus Linux), chosen because it's free, open-source, and already familiar to the whole team from coursework.

## AI Agents

MediSlot uses AI agents to handle tasks that would otherwise require a human doctor, pharmacist, or admin to answer manually.

| Agent | Role |
|---|---|
| **Medicine Assistant** | Powered by Gemini. Patients can search any medicine or click on a prescribed one to get AI-generated info: uses, side effects, dosage guidelines, precautions, and drug interactions. |
| **Doctor Matching Agent** | Suggests the most relevant doctor based on a patient's symptoms, specialty needs, location, and budget, instead of manual filtering alone. |
| **Appointment Reminder Agent** | Sends automated reminders before an upcoming appointment and follow-up nudges after a missed slot. |
| **Triage Assistant** | Asks a patient a few quick questions and recommends the right specialty before they book, so patients don't guess which doctor to see. |


## Project structure

```
medislot/
├── config.php                 # Database connection & app settings
├── header.php                 # Common header / navbar
├── footer.php                 # Common footer
├── home.php                   # Landing page
├── login.php                  # User login
├── logout.php                 # Session destroy
├── signup.php                 # Patient registration
├── signup_doctor.php          # Doctor registration
├── index.php                  # Patient dashboard (old)
│
├── patient/                   # Patient module
│   ├── dashboard.php          # Patient home
│   ├── search_doctors.php     # Search & filter doctors
│   ├── doctor_profile.php     # Doctor details + time slots
│   ├── book_appointment.php   # Book appointment
│   ├── payment.php            # Payment processing
│   ├── appointments.php       # View / cancel appointments
│   ├── appointment_reminder.php # AI appointment reminder
│   ├── medicine_assistant.php # Medicine AI (Gemini)
│   ├── doctor_matching.php    # AI doctor matching
│   ├── prescriptions.php      # View prescriptions
│   ├── medical_records.php    # View medical records
│   └── profile.php            # Edit patient profile
│
├── doctor/                    # Doctor module
│   ├── dashboard.php          # Doctor home
│   ├── view_appointments.php  # Manage appointments
│   ├── issue_prescription.php # Issue prescription
│   ├── patient_records.php    # View patient records
│   └── profile.php            # Edit doctor profile
│
├── tests/                     # Unit testing
│   ├── PasswordTest.php
│   ├── ValidationTest.php
│   └── AppointmentStatusTest.php
│
├── phpunit.xml                # PHPUnit configuration
├── composer.json              # Composer dependencies
└── README.md
```
### Unit Testing
Unit testing has been implemented in this project using PHPUnit to ensure the correctness and reliability of core functionalities.

### Purpose
The main goal of unit testing in this project is to verify that important parts of the system work correctly, such as:

*Password hashing and verification

*Email validation

*Input validation

*Appointment status validation

### Tools Used

*PHPUnit 11

*Composer

### Test Structure
```
tests/
├── PasswordTest.php
├── ValidationTest.php
└── AppointmentStatusTest.php
```
### How to Run the Tests

1. Install the required packages:
```
composer install
```
2. Run the unit tests:
```
php vendor/bin/phpunit
```

### Test Results
```
OK (6 tests, 11 assertions)
```
All test cases passed successfully, which confirms that the core logic of the MediSlot system is working properly.
