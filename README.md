Patient Ticketing Module
========================

This module is intended to provide a generic ticketing mechanism to track patients through multiple pathways. As a standalone module, it will require other modules to integrate with it to create patient tickets. In its first iteration it is intended to support Virtual Clinics, but it is being implemented in a way to be suitable for clinic tracking, application processing and (hopefully) any other pathway that might occur for a patient.

Setup
=====

1. Place the module code in the usual modules directory (protected/modules)
2. Add the module to the yii local config:

    'modules' => array(
        ...
        'PatientTicketing' => array('class' => array('class' => '\OEModule\PatientTicketing\PatientTicketingModule'),
        ...
    )
3. In user admin, give the users you want to have access to Patient Ticketing the Patient Ticket permission.
4. Use the Patient Ticketing admin to setup one or more queue.

Raising Tickets
===============

At the moment, tickets can only be raised in supporting modules:
1. OphCiExamination - The Clinic Outcome element



