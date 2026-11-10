<?php
// Blocks anyone from opening this file directly in a browser
if (!defined('APP_MAIL')) {
    http_response_code(403);
    exit;
}

/*
 * DRAFT Terms and Conditions for the "Registration Approved" email.
 * This is a general working draft written for testing. Anything marked TODO
 * must be replaced with the real wording/figures from Best Politician Assembly
 * (refund rules, deadlines, legal entity, governing law, etc.) before real use.
 *
 * Format:
 *   [
 *     'title'   => '1. Section heading',      // must start with the section number
 *     'clauses' => [
 *        ['Clause title.', 'Clause text'],                            // plain clause
 *        ['Clause title.', 'Clause text', ['bullet 1', 'bullet 2']],  // clause with bullets
 *     ],
 *   ]
 *
 * Clauses are numbered automatically (1.1, 1.2, ...). All text is escaped by the email.
 * Write {click here} anywhere in a text to turn it into a link to the visa page.
 */
return [
    [
        'title' => '1. Eligibility, Registration & Travel Documents',
        'clauses' => [
            ['Eligibility.', 'The conference is intended for delegates who are interested in diplomacy, leadership, politics and public affairs. TODO: add minimum age and any other entry requirements.'],
            ['Official Channels.', 'Registration and payment must be made only through our official website and the payment pages linked in our emails. We cannot accept responsibility for money sent to any other person, account or website.'],
            ['Correct Details.', 'Please make sure the information you submit is accurate and up to date, including your name exactly as it appears on your passport. Mistakes may delay your documents or access to the event.'],
            ['Visa and Travel Documents.', 'Participants are responsible for their own visa, travel documents and travel insurance, except if you are part of our Premium Visa Support package. {click here} to request visa premium support.'],
            ['Approval.', 'Approval of an application is at the discretion of the organisers. An approval may be withdrawn if the information provided is found to be false or misleading.'],
        ],
    ],
    [
        'title' => '2. Fees & Payment',
        'clauses' => [
            ['Fees.', 'The fees that apply are the ones shown in the payment tables above and on the payment page. Bank charges, currency conversion costs and any local taxes on your payment are paid by you. TODO: confirm what the fee includes.'],
            ['Seat Confirmation.', 'Your seat is only confirmed once the registration payment has been received according to the schedule above.'],
            ['Payment Methods.', 'Please pay only by the methods listed on the payment page. Keep your proof of payment until you receive your confirmation.'],
            ['Installments.', 'If you choose the installment method, every installment must be paid on or before its deadline. TODO: state what happens when a deadline is missed.'],
            ['Services After Payment.', 'TODO: list the services that start after payment (for example delegate ID, portal access, scheduling, visa support documents).'],
        ],
    ],
    [
        'title' => '3. Cancellations, Refunds & Transfers',
        'clauses' => [
            ['Refund Policy.', 'TODO: replace with Best Politician Assembly refund policy.'],
            ['Transfers and Vouchers.', 'TODO: replace with Best Politician Assembly transfer/voucher rules, including who may apply and how to apply.', [
                'TODO: eligibility condition for a transfer or voucher.',
                'TODO: request deadline.',
                'TODO: charges that apply to late requests.',
            ]],
            ['Voucher Validity.', 'TODO: replace with validity period and conditions of any voucher.'],
        ],
    ],
    [
        'title' => '4. Billing Questions',
        'clauses' => [
            ['Contact Us First.', 'If you think a charge is wrong, please write to our contact email as soon as possible, with your payment details, so we can look into it. TODO: confirm the time limit for raising billing issues.'],
            ['Your Rights.', 'Nothing in these terms limits any rights you have under consumer protection laws that apply to you.'],
        ],
    ],
    [
        'title' => '5. Programme Changes & Unforeseen Events',
        'clauses' => [
            ['Programme Changes.', 'The organisers may adjust the programme, speakers, format, dates or venue where necessary and will inform participants by email.'],
            ['Events Beyond Our Control.', 'We are not responsible for delays, changes or cancellations caused by events outside our reasonable control. TODO: state what is offered to participants in that situation.'],
            ['Committee and Role Allocation.', 'TODO: explain how roles, committees or simulations are allocated and whether preferences are guaranteed.'],
        ],
    ],
    [
        'title' => '6. Conduct, Safety & Risk',
        'clauses' => [
            ['Conduct.', 'All participants are expected to behave respectfully and professionally. Participants who disrupt the event or act unlawfully or unsafely may be asked to leave. TODO: state whether any refund applies in that case.'],
            ['Personal Safety.', 'You are responsible for your own belongings and personal safety during the event and while travelling.'],
            ['Participation Risk.', 'Taking part is voluntary and involves normal travel and group activity risks. TODO: replace with the approved risk wording.'],
        ],
    ],
    [
        'title' => '7. Intellectual Property & Media',
        'clauses' => [
            ['Event Materials.', 'Programme content, logos and materials belong to the organisers or their licensors. They may not be copied, sold or shared publicly without written permission.'],
            ['Photos and Video.', 'Photos and recordings may be taken during the event and used for reporting and promotion. TODO: state how a participant can opt out.'],
        ],
    ],
    [
        'title' => '8. Data Protection & Communications',
        'clauses' => [
            ['Privacy.', 'Your personal information is handled in line with our Privacy Policy and the laws that apply. TODO: confirm the privacy policy link.'],
            ['Event Messages.', 'By registering, you agree to receive messages about your registration and the event by email, phone or app.'],
            ['Account Security.', 'Please keep any login details we give you private and tell us at once if you suspect someone else has used them.'],
        ],
    ],
    [
        'title' => '9. Third-Party Services',
        'clauses' => [
            ['Third Parties.', 'Venues, payment providers and other partners may have their own terms and privacy notices. We are not responsible for the actions of third parties. TODO: confirm wording.'],
        ],
    ],
    [
        'title' => '10. Disclaimers & Limits of Liability',
        'clauses' => [
            ['Disclaimer.', 'TODO: replace with Best Politician Assembly disclaimer.'],
            ['Limit of Liability.', 'TODO: replace with Best Politician Assembly liability limit, as approved by your legal adviser.'],
        ],
    ],
    [
        'title' => '11. Governing Law & Disputes',
        'clauses' => [
            ['Governing Law.', 'TODO: state which country or state law applies to these terms.'],
            ['Resolving Disputes.', 'TODO: state how disputes are handled (for example discussion first, then mediation or courts) and where.'],
        ],
    ],
    [
        'title' => '12. General Provisions',
        'clauses' => [
            ['Notices.', 'We may send notices to the email address you gave when you registered. TODO: add the official email for legal notices.'],
            ['Updates.', 'We may update these terms. The version published on our website is the current one.'],
            ['Separate Provisions.', 'If any part of these terms is found to be invalid, the rest remains in force.'],
            ['Acceptance.', 'By making a payment or attending the event, you confirm that you accept these terms.'],
        ],
    ],
];