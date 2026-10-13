@props(['url', 'label'])

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
    <tr>
        <td style="background:#FF5A1F;border-radius:10px;">
            <a href="{{ $url }}"
               style="display:inline-block;padding:13px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
