<?php

namespace App\Support;

class DefaultTemplates
{
    public static function forType(string $type): string
    {
        return match ($type) {
            'prescription'   => self::prescription(),
            'invoice'        => self::invoice(),
            'receipt'        => self::receipt(),
            'lab_report'     => self::labReport(),
            'imaging_report' => self::imagingReport(),
            'certificate'    => self::certificate(),
            'referral'       => self::referral(),
            'sick_leave'     => self::sickLeave(),
            'fit_to_work'    => self::fitToWork(),
            default          => self::generic(),
        };
    }

    private static function header(string $title): string
    {
        return '<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1e40af;padding-bottom:14px;margin-bottom:20px;">'
            . '<div>'
            . '<div style="font-size:22px;font-weight:700;color:#1e40af;">{{branch_name}}</div>'
            . '<div style="font-size:10px;color:#555;margin-top:3px;">{{branch_address}}</div>'
            . '<div style="font-size:10px;color:#555;">Tel: {{branch_phone}} &bull; {{branch_email}}</div>'
            . '</div>'
            . '<div style="text-align:right;">'
            . '<div style="font-size:16px;font-weight:700;color:#1e40af;letter-spacing:1px;">' . htmlspecialchars($title) . '</div>'
            . '<div style="font-size:10px;color:#777;margin-top:4px;">No. {{document_number}}</div>'
            . '<div style="font-size:10px;color:#777;">{{date}}</div>'
            . '</div>'
            . '</div>';
    }

    private static function patientBar(bool $includeGender = false): string
    {
        $gender = $includeGender
            ? '<div style="flex:1;padding:10px 14px;border-right:1px solid #dde;background:#f8f9fb;">'
              . '<div style="font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.5px;margin-bottom:3px;">Gender</div>'
              . '<div style="font-weight:600;">{{patient_gender}}</div></div>'
            : '';

        return '<div style="display:flex;border:1px solid #dde;border-radius:4px;overflow:hidden;margin-bottom:18px;">'
            . '<div style="flex:2;padding:10px 14px;border-right:1px solid #dde;background:#f8f9fb;">'
            . '<div style="font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.5px;margin-bottom:3px;">Patient</div>'
            . '<div style="font-weight:600;">{{patient_name}}</div></div>'
            . '<div style="flex:1;padding:10px 14px;border-right:1px solid #dde;background:#f8f9fb;">'
            . '<div style="font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.5px;margin-bottom:3px;">Patient #</div>'
            . '<div style="font-weight:600;">{{patient_number}}</div></div>'
            . '<div style="flex:1;padding:10px 14px;border-right:1px solid #dde;background:#f8f9fb;">'
            . '<div style="font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.5px;margin-bottom:3px;">Date of Birth</div>'
            . '<div style="font-weight:600;">{{patient_dob}}</div></div>'
            . $gender
            . '<div style="flex:2;padding:10px 14px;background:#f8f9fb;">'
            . '<div style="font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.5px;margin-bottom:3px;">Physician</div>'
            . '<div style="font-weight:600;">{{doctor_name}}</div></div>'
            . '</div>';
    }

    private static function signature(): string
    {
        return '<div style="margin-top:40px;display:flex;justify-content:flex-end;">'
            . '<div style="width:220px;text-align:center;border-top:1px solid #333;padding-top:6px;">'
            . '<div style="font-size:10px;font-weight:600;">{{doctor_name}}</div>'
            . '<div style="font-size:9px;color:#666;">Authorised Signature</div>'
            . '<div style="font-size:9px;color:#888;margin-top:2px;">{{date}}</div>'
            . '</div></div>';
    }

    private static function footer(string $dept = ''): string
    {
        $deptStr = $dept ? ' &mdash; ' . htmlspecialchars($dept) : '';
        return '<div style="margin-top:16px;padding-top:12px;border-top:1px solid #dde;display:flex;justify-content:space-between;font-size:9px;color:#888;">'
            . '<span>{{branch_name}}' . $deptStr . '</span>'
            . '<span>{{document_number}} &mdash; {{date}}</span>'
            . '</div>';
    }

    private static function prescription(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . self::header('PRESCRIPTION')
            . self::patientBar()
            . '<div style="margin-bottom:30px;min-height:200px;">{{content}}</div>'
            . '<div style="font-size:9px;color:#aaa;font-style:italic;margin-bottom:20px;">This prescription is valid for 30 days from the date of issue. Dispensed medication must be taken as directed.</div>'
            . self::signature()
            . self::footer('Pharmacy')
            . '</div>';
    }

    private static function invoice(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . self::header('TAX INVOICE')
            . '<div style="display:flex;gap:40px;margin-bottom:20px;">'
            . '<div><div style="font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.5px;margin-bottom:4px;">Bill To</div>'
            . '<div style="font-weight:600;">{{patient_name}}</div>'
            . '<div style="font-size:10px;color:#555;">Patient #: {{patient_number}}</div></div>'
            . '<div><div style="font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.5px;margin-bottom:4px;">Invoice Details</div>'
            . '<div style="font-size:10px;">Invoice #: <strong>{{document_number}}</strong></div>'
            . '<div style="font-size:10px;">Date: <strong>{{date}}</strong></div></div>'
            . '</div>'
            . '<div style="margin-bottom:20px;">{{content}}</div>'
            . self::footer('Accounts')
            . '</div>';
    }

    private static function receipt(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . self::header('PAYMENT RECEIPT')
            . '<div style="margin-bottom:16px;"><span style="font-weight:600;">Received from:</span> {{patient_name}} ({{patient_number}})</div>'
            . '<div style="margin-bottom:20px;">{{content}}</div>'
            . '<div style="font-size:9px;color:#aaa;font-style:italic;margin-bottom:16px;">This is an official receipt. Please retain for your records.</div>'
            . self::signature()
            . self::footer('Accounts')
            . '</div>';
    }

    private static function labReport(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . self::header('LABORATORY REPORT')
            . self::patientBar(true)
            . '<div style="margin-bottom:20px;">{{content}}</div>'
            . '<div style="font-size:8.5px;color:#aaa;font-style:italic;margin-bottom:20px;">This report is intended for the named patient and ordering physician only. Results should be interpreted in the context of clinical findings.</div>'
            . self::signature()
            . self::footer('Laboratory Services')
            . '</div>';
    }

    private static function imagingReport(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . self::header('IMAGING REPORT')
            . self::patientBar(true)
            . '<div style="margin-bottom:20px;">{{content}}</div>'
            . '<div style="font-size:8.5px;color:#aaa;font-style:italic;margin-bottom:20px;">Imaging results should be correlated with clinical findings. This report is intended for the named patient and their treating physician only.</div>'
            . self::signature()
            . self::footer('Diagnostic Imaging')
            . '</div>';
    }

    private static function certificate(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . '<div style="text-align:center;margin-bottom:30px;">'
            . '<div style="font-size:22px;font-weight:700;color:#1e40af;">{{branch_name}}</div>'
            . '<div style="font-size:10px;color:#555;margin-top:3px;">{{branch_address}} &bull; Tel: {{branch_phone}}</div>'
            . '<div style="margin-top:20px;font-size:20px;font-weight:700;color:#1e40af;letter-spacing:2px;border-bottom:3px solid #1e40af;padding-bottom:10px;display:inline-block;">MEDICAL CERTIFICATE</div>'
            . '<div style="font-size:10px;color:#777;margin-top:8px;">Certificate No. {{document_number}} &bull; {{date}}</div>'
            . '</div>'
            . '<div style="margin-bottom:20px;line-height:1.8;">This is to certify that <strong>{{patient_name}}</strong> (Patient #: {{patient_number}}, DOB: {{patient_dob}}) was examined by <strong>{{doctor_name}}</strong> on {{date}}.</div>'
            . '<div style="margin-bottom:30px;min-height:120px;">{{content}}</div>'
            . self::signature()
            . self::footer()
            . '</div>';
    }

    private static function referral(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . self::header('REFERRAL LETTER')
            . '<div style="margin-bottom:16px;"><div style="font-size:10px;color:#888;">Date: {{date}}</div></div>'
            . '<div style="margin-bottom:14px;"><strong>Re: {{patient_name}}</strong><br><span style="color:#555;">Patient #: {{patient_number}} &bull; DOB: {{patient_dob}}</span></div>'
            . '<div style="margin-bottom:30px;line-height:1.8;">{{content}}</div>'
            . '<div style="margin-bottom:4px;">Yours sincerely,</div>'
            . self::signature()
            . self::footer()
            . '</div>';
    }

    private static function sickLeave(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . '<div style="text-align:center;margin-bottom:28px;">'
            . '<div style="font-size:22px;font-weight:700;color:#1e40af;">{{branch_name}}</div>'
            . '<div style="font-size:10px;color:#555;margin-top:3px;">{{branch_address}} &bull; Tel: {{branch_phone}} &bull; {{branch_email}}</div>'
            . '<div style="margin-top:18px;font-size:19px;font-weight:700;color:#1e40af;letter-spacing:2px;border-bottom:3px solid #1e40af;padding-bottom:10px;display:inline-block;">SICK LEAVE CERTIFICATE</div>'
            . '<div style="font-size:10px;color:#777;margin-top:8px;">Certificate No. {{document_number}} &bull; {{date}}</div>'
            . '</div>'
            . '<div style="margin-bottom:18px;line-height:2.0;font-size:11px;">'
            . 'This is to certify that <strong>{{patient_name}}</strong> (Patient #: {{patient_number}}, DOB: {{patient_dob}}) '
            . 'was examined on <strong>{{date}}</strong> by <strong>{{doctor_name}}</strong> and is found to be unfit for duty.'
            . '</div>'
            . '<div style="background:#f8f9fb;border:1px solid #dde;border-radius:4px;padding:14px 18px;margin-bottom:18px;">'
            . '<div style="font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.5px;margin-bottom:8px;">Medical Details</div>'
            . '<div style="min-height:80px;">{{content}}</div>'
            . '</div>'
            . '<div style="margin-bottom:18px;line-height:2.0;font-size:11px;">'
            . 'The patient is recommended to rest and is excused from work / school / duties for the period as indicated above. '
            . 'A follow-up review should be conducted as advised by the attending physician.'
            . '</div>'
            . '<div style="font-size:8.5px;color:#aaa;font-style:italic;margin-bottom:24px;">'
            . 'This certificate is issued in good faith based on the clinical examination conducted on the date stated above.'
            . '</div>'
            . self::signature()
            . self::footer()
            . '</div>';
    }

    private static function fitToWork(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . '<div style="text-align:center;margin-bottom:28px;">'
            . '<div style="font-size:22px;font-weight:700;color:#1e40af;">{{branch_name}}</div>'
            . '<div style="font-size:10px;color:#555;margin-top:3px;">{{branch_address}} &bull; Tel: {{branch_phone}} &bull; {{branch_email}}</div>'
            . '<div style="margin-top:18px;font-size:19px;font-weight:700;color:#1e40af;letter-spacing:2px;border-bottom:3px solid #1e40af;padding-bottom:10px;display:inline-block;">FIT TO WORK CERTIFICATE</div>'
            . '<div style="font-size:10px;color:#777;margin-top:8px;">Certificate No. {{document_number}} &bull; {{date}}</div>'
            . '</div>'
            . '<div style="margin-bottom:18px;line-height:2.0;font-size:11px;">'
            . 'This is to certify that <strong>{{patient_name}}</strong> (Patient #: {{patient_number}}, DOB: {{patient_dob}}) '
            . 'was examined on <strong>{{date}}</strong> by <strong>{{doctor_name}}</strong> and is found to be <strong>fit to resume normal duties</strong>.'
            . '</div>'
            . '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:4px;padding:14px 18px;margin-bottom:18px;">'
            . '<div style="font-size:9px;text-transform:uppercase;color:#166534;letter-spacing:.5px;margin-bottom:8px;">Clinical Notes &amp; Conditions</div>'
            . '<div style="min-height:80px;">{{content}}</div>'
            . '</div>'
            . '<div style="margin-bottom:18px;line-height:2.0;font-size:11px;">'
            . 'The above-named individual has been assessed as medically fit to return to work / resume normal activities. '
            . 'Any specific restrictions or accommodations, if applicable, are noted above.'
            . '</div>'
            . '<div style="font-size:8.5px;color:#aaa;font-style:italic;margin-bottom:24px;">'
            . 'This certificate is issued in good faith based on the clinical examination conducted on the date stated above.'
            . '</div>'
            . self::signature()
            . self::footer()
            . '</div>';
    }

    private static function generic(): string
    {
        return '<div class="doc-page" style="font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a2e;padding:32px 40px;">'
            . self::header('DOCUMENT')
            . self::patientBar()
            . '<div style="margin-bottom:30px;min-height:200px;">{{content}}</div>'
            . self::signature()
            . self::footer()
            . '</div>';
    }
}
