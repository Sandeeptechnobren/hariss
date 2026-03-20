<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Refrigerator Agreement</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #000000;
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body style="">
    <div class="page-border">
  <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
    <tr>
        <td style="width:40%; vertical-align:top;">
            <img src="../../osa_developmentV2/storage/app/public/image/hariss.png" alt="Hariss International Pvt. Ltd." height="65px">
        </td>

        <td style="width:60%; vertical-align:top;">
            <div style="text-align:left; font-size:12px; color:#000; margin:3px;">
                <b>
                    Plot 32/33, Bombo Road, Kawempe. P.O. Box 12270, Kampala Uganda<br>
                    <strong>Tel:</strong> +256 204 001 000 
                    &nbsp;|&nbsp; 
    <br>
                    <strong>Email:</strong> info@harissint.com

                    <strong>Website:</strong> www.harissint.com
                </b>
            </div>
        </td>
    </tr>
</table>
    <hr style="width:100%; height:2px; background-color:red; border:none; margin:0px;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:33.33%; text-align:left; font-weight:bold;">
                Doc. No. HI-MA-F8069
            </td>

            <td style="width:33.33%; text-align:center; font-weight:bold;">
                Rev. No. 0
            </td>

            <td style="width:33.33%; text-align:right; font-weight:bold;">
                Effective Date: 10/05/2020
            </td>
        </tr>
    </table>
    <hr style="width:100%; height:2px; background-color:red; border:none;margin:0px;">
</div>
<table style="width:100%; border-collapse:collapse;margin-bottom:5px;">
    <tr>
        <td style="text-align:center; font-weight:bold; text-transform:uppercase; padding-top:4px; font-size:12px; color:black;">
            The Republic of Uganda
<br>
            In the matter of the Contracts Act No. 7 of 2010
            <br>


            And
<br>
            In the matter of provision of Riham
            <br>
            Refrigerators for Sales Promotion
        </td>
    </tr>
</table>
@php
    $date = \Carbon\Carbon::parse($agreement->created_at);
@endphp
<table style="width:100%; border-collapse:collapse; font-size:12px; line-height:1.8; text-align:center;">
    <tr>
        <td colspan="4" style="padding-bottom:10px;">
            This Agreement is entered this 
            <span style="display:inline-block; border-bottom:1px dotted #000; min-width:60px; text-align:center;">
                {{ $date->format('j') }}<sup>{{ $date->format('S') }}</sup>
            </span>
            day of 
            <span style="display:inline-block; border-bottom:1px dotted #000; min-width:120px; text-align:center;">
                {{ $date->format('F') }}
            </span>
            20 
            <span style="display:inline-block; border-bottom:1px dotted #000; min-width:40px; text-align:center;">
                {{ $date->format('y') }}
            </span>
        </td>
    </tr>
    <tr>
        <td colspan="4" style="padding:5px 0;">
            BETWEEN
        </td>
    </tr>
    <tr>
        <td colspan="4" style="padding-bottom:5px;">
            <span style="">HARISS INTERNATIONAL LIMITED</span>
            OF Plot 32/33 Bombo Road Kawempe, P.O. Box 24972 Kampala, Uganda,<br>
            (Hereinafter referred to as "THE COMPANY")
        </td>
    </tr>
    <tr>
        <td colspan="4" style="padding-bottom:14px;">
            AND
        </td>
    </tr>
    <br>
    <br>
    <tr>
        <td style="width:6%; text-align:right;">M/S&nbsp;</td>

        <td style="width:44%; text-align:left;">
            <span style="display:inline-block; border-bottom:1px dotted #000; width:95%;">
                {{ $agreement->ms }}
            </span>
        </td>

        <td style="width:6%; text-align:left;">Of&nbsp;</td>

        <td style="width:44%; text-align:left;">
            <span style="display:inline-block; border-bottom:1px dotted #000; width:95%;">
                {{ $agreement->ms_of }}
            </span>
        </td>
    </tr>

    <tr>
        <td style="text-align:right;">(Address)&nbsp;</td>

        <td colspan="3" style="text-align:left;">
            <span style="display:inline-block; border-bottom:1px dotted #000; width:98%;">
                {{ $agreement->address }}
            </span>
        </td>
    </tr>
    <tr>
        <td colspan="4" style="padding-top:20px;">
            Hereinafter referred to as ("THE RECEIVING ENTITY")
        </td>
    </tr>

</table>

        <div class="text-justify mb-3"
            style=" padding: 4px;">
           <span style="line-height:20px;"> WHEREAS,</span><br><br> "THE COMPANY" is a renowned Foods and Beverages Manufacturer in Uganda and is desirous of enhancing its brand awareness and
galvanizing its brand platform on the Ugandan Market , the company wishes to provide Refrigerators to the receiving entity to ensure
display of its flagship beverages and to ensure that the customers are able to access cold beverages,<br><br>
<span style="margin-top:5px;margin-bottom:5px;"> WHEREAS,</span><br><br>  "THE RECEIVING ENTITY" is a distributor/ wholesaler/ retailer of the Company's beverages in Uganda and further would like to enhance
the service of the company's beverages to the customers at the optimum consumption temperature,
        </div>
        <br>
        <div style="font-weight:bold; text-align:left;margin-top:5px;margin-bottom:5px;">
            NOW THEREFORE THIS AGREEMENT WITNESSETH AS FOLLOWS:
        </div>
        <br>
        <br>

        <!-- Clauses -->
        <div style="font-weight:bold;">1. <u>TERM</u></div> 
        <div class="clause-content text-justify">
            The term for this Agreement (the "Term") shall run from the date of signature for a duration of three (3)
            years
            and may be renewed for such other period as the company shall deem fit and proper.
        </div>
        <br><br><br>
        <div style="font-weight:bold;">2. <u>OWNERSHIP OF REFRIGERATOR/S/Ice Cooler</u></div>
        <div class="clause-content text-justify">
            Ownership of the refrigerators shall remain vested in the company at all times and the receiving entity
            shall
            have no entitlement to the supplied refrigerators. Failure to return the refrigerators when recalled will
            result
            in the receiving entity being charged, and the landlord of the premises shall have no rights over the
            refrigerators at any time.
        </div>
        
        <br><br><br><br><br><br>

            <div class="page-border">
  <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
    <tr>
        <td style="width:40%; vertical-align:top;">
            <img src="../../osa_developmentV2/storage/app/public/image/hariss.png" alt="Hariss International Pvt. Ltd." height="65px">
        </td>

        <td style="width:60%; vertical-align:top;">
            <div style="text-align:left; font-size:12px; color:#000; margin:3px;">
                <b>
                    Plot 32/33, Bombo Road, Kawempe. P.O. Box 12270, Kampala Uganda<br>
                    <strong>Tel:</strong> +256 204 001 000 
                    &nbsp;|&nbsp; 
    <br>
                    <strong>Email:</strong> info@harissint.com

                    <strong>Website:</strong> www.harissint.com
                </b>
            </div>
        </td>
    </tr>
</table>
<hr style="width:100%; height:2px; background-color:red; border:none; margin:0px;">
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:33.33%; text-align:left; font-weight:bold;">
            Doc. No. HI-MA-F8069
        </td>

        <td style="width:33.33%; text-align:center; font-weight:bold;">
            Rev. No. 0
        </td>

        <td style="width:33.33%; text-align:right; font-weight:bold;">
            Effective Date: 10/05/2020
        </td>
    </tr>
</table>
<hr style="width:100%; height:2px; background-color:red; border:none; margin:0px;">

</div>
<br>
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td colspan="2" style="font-weight:bold;">
            3.&nbsp;<span style="text-decoration:underline;">THE RECEIVING ENTITY COVENANTS</span>
        </td>
    </tr>

    <tr>
        <td style="width:18px; vertical-align:top; font-weight:bold;">a)</td>
        <td>The refrigerator shall be used solely for cooling and display of the Company's beverages' at all times. The receiving entity shall continue purchasing reasonable quantities and shall not display competitor's products.</td>
    </tr>

    <tr>
        <td style="vertical-align:top; font-weight:bold;">b)</td>
        <td>The receiving entity shall position the refrigerator at the approved business location and shall not remove it without written notice. Failure allows the company to repossess the refrigerator.</td>
    </tr>

    <tr>
        <td style="vertical-align:top; font-weight:bold;">c)</td>
        <td>The refrigerator must remain switched on 24 hours daily, hygienically clean, fully stocked, and properly positioned. Branding shall not be removed or defaced.</td>
    </tr>

    <tr>
        <td style="vertical-align:top; font-weight:bold;">d)</td>
        <td>The receiving entity shall not sublet or transfer the unit without notifying the company.</td>
    </tr>

    <tr>
        <td style="vertical-align:top; font-weight:bold;">e)</td>
        <td>If lost, stolen, or damaged, the receiving entity shall bear the full replacement cost.</td>
    </tr>

    <tr>
        <td style="vertical-align:top; font-weight:bold;">f)</td>
        <td>The merchandiser of Hariss International Ltd shall be permitted to organize, restock, follow the planogram, clean products, and monitor expiries.</td>
    </tr>
    <br><br>

    <!-- SECTION 4 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            4.&nbsp;<span style="text-decoration:underline;">THE COMPANY COVENANTS</span>
        </td>
    </tr>

    <tr>
        <td style="vertical-align:top; font-weight:bold;">a)</td>
        <td>Maintenance and repairs shall be carried out only by certified employees of Hariss International Ltd. Breach results in termination and recall.</td>
    </tr>

    <tr>
        <td style="vertical-align:top; font-weight:bold;">b)</td>
        <td>The Company reserves the right to recall or repossess the refrigerator without giving reasons.</td>
    </tr>

    <tr>
        <td style="vertical-align:top; font-weight:bold;">c)</td>
        <td>The Company may terminate this contract at its sole discretion without prior notice or compensation.</td>
    </tr>

<br>
    <!-- SECTION 5 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            5.&nbsp;<span style="text-decoration:underline;">LIABILITY FOR USE OF THE REFRIGERATOR/Ice Cooler</span>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            Liability for injury, disability, or death caused by operation of the refrigerator shall be borne by the receiving entity, which shall indemnify the company against any resulting loss.
        </td>
    </tr>

<br>
    <!-- SECTION 6 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            6.&nbsp;<span style="text-decoration:underline;">WARRANTIES / REPRESENTATIONS / INDEMNITY / INSURANCE</span>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            Each party warrants authority to enter this Agreement. The receiving entity warrants that all persons handling the refrigerator are at least eighteen (18) years of age.
        </td>
    </tr>

<br>
    <!-- SECTION 7 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            7.&nbsp;<span style="text-decoration:underline;">COMPLIANCE WITH LAWS AND REGULATIONS</span>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            The receiving entity shall comply with all applicable national laws, regulations, and company policies regarding product sales and refrigerator usage.
        </td>
    </tr>

<br>
    <!-- SECTION 8 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            8.&nbsp;<span style="text-decoration:underline;">ASSIGNMENT</span>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            Neither party may assign this Agreement or delegate duties without prior written consent of the other party.
        </td>
    </tr>

<br>
    <!-- SECTION 9 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            9.&nbsp;<span style="text-decoration:underline;">DEBARMENT</span>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            The receiving entity warrants it has not been and will not be debarred by any authority from providing services in its distributor capacity.
        </td>
    </tr>

<br>
    <!-- SECTION 10 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            10.&nbsp;<span style="text-decoration:underline;">SUCCESSORS AND ASSIGNS</span>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            This Agreement shall bind and benefit the parties and their respective heirs, representatives, successors, and assigns.
        </td>
    </tr>

</table>
    <div class="page-border">
  <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
    <tr>
        <td style="width:40%; vertical-align:top;">
            <img src="../../osa_developmentV2/storage/app/public/image/hariss.png" alt="Hariss International Pvt. Ltd." height="65px">
        </td>

        <td style="width:60%; vertical-align:top;">
            <div style="text-align:left; font-size:12px; color:#000; margin:3px;">
                <b>
                    Plot 32/33, Bombo Road, Kawempe. P.O. Box 12270, Kampala Uganda<br>
                    <strong>Tel:</strong> +256 204 001 000 
                    &nbsp;|&nbsp; 
    <br>
                    <strong>Email:</strong> info@harissint.com

                    <strong>Website:</strong> www.harissint.com
                </b>
            </div>
        </td>
    </tr>
</table>
    <hr style="width:100%; height:2px; background-color:red; border:none; margin:0px;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:33.33%; text-align:left; font-weight:bold;">
                Doc. No. HI-MA-F8069
            </td>

            <td style="width:33.33%; text-align:center; font-weight:bold;">
                Rev. No. 0
            </td>

            <td style="width:33.33%; text-align:right; font-weight:bold;">
                Effective Date: 10/05/2020
            </td>
        </tr>
    </table>
    <hr style="width:100%; height:2px; background-color:red; border:none;margin:0px;">
</div>
<br>
<div style="width:100%; font-size:12px; line-height:1.4; letter-spacing:0.2px; color:#000;">

<!-- ===================== MAIN CONTENT ===================== -->


<table style="width:100%; border-collapse:collapse;">

    <!-- 11 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            11.&nbsp;<span style="text-decoration:underline;">ENTIRE AGREEMENT</span>
        </td>
    </tr>
    <tr>
        <td style="text-align:justify; padding-bottom:6px;">
            This Agreement contains the entire understanding of the parties with respect to the matters herein contained and supersedes all previous agreements and undertakings with respect thereto. This Agreement may be modified only by the written agreement signed by the parties.
        </td>
    </tr>

    <!-- 12 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            12.&nbsp;<span style="text-decoration:underline;">OBLIGATION OF LICENSED AGENT</span>
        </td>
    </tr>
    <tr>
        <td style="text-align:justify; padding-bottom:6px;">
            It shall be the sole responsibility of the Licensed Agent/Distributor in the Territory of the receiving entity to ensure that the receiving party complies with company policies in terms of stock purchases and refrigerator usage.
        </td>
    </tr>

    <!-- 13 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            13.&nbsp;<span style="text-decoration:underline;">ASSET DETAILS</span>
        </td>
    </tr>

    <tr>
        <td style="padding-top:3px; padding-bottom:3px;">
            <strong>Asset Number (Hariss International Ltd):</strong>
            <span style="display:inline-block; border-bottom:1px dotted #000; width:62%;">
                {{ $agreement->asset_number ?? '-' }}
            </span>
        </td>
    </tr>

    <tr>
        <td style="padding-top:3px; padding-bottom:3px;">
            <strong>Machine Serial Number:</strong>
            <span style="display:inline-block; border-bottom:1px dotted #000; width:78%;">
                {{ $agreement->serial_number ?? '-' }}
            </span>
        </td>
    </tr>

    <tr>
        <td style="padding-top:3px; padding-bottom:6px;">
            <strong>Model & Branding of the Refrigerator/Ice Cooler:</strong>
            <span style="display:inline-block; border-bottom:1px dotted #000; width:56%;">
                {{ $agreement->model_branding ?? '-' }}
            </span>
        </td>
    </tr>

    <!-- 14 -->
    <tr>
        <td colspan="2" style="font-weight:bold; padding-top:6px;">
            14.&nbsp;<span style="text-decoration:underline;">GOVERNING LAW</span>
        </td>
    </tr>
    <tr>
        <td style="text-align:justify; padding-bottom:10px;">
            This Agreement shall be governed by and construed in accordance with the laws of the Republic of Uganda and severance of one part of the agreement for any invalidity does not invalidate the whole agreement.
        </td>
    </tr>

</table>
<table style="width:100%; border-collapse:collapse; margin-top:8px; line-height:1.6;">

    <tr>
        <td style="width:50%;  padding-bottom:6px;">
            FOR AND ON BEHALF OF HARISS INTERNATIONAL LIMITED,
        </td>
        <td style="width:50%; padding-bottom:6px;">
            FOR AND ON BEHALF OF RECEIVING ENTITY,
        </td>
    </tr>

    <tr>
        <td>
            Name & Contact No:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:65%;">
                {{ $agreement->behaf_hariss_name_contact ?? '-' }}
            </span>
        </td>
        <td>
            Name & Contact No:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:65%;">
                {{ $agreement->behaf_reciver_name_contact ?? '-' }}
            </span>
        </td>
    </tr>

    <tr>
        <td style="padding-top:6px;">
            Signature:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:78%; vertical-align:middle">
            @if($agreement->behaf_hariss_sign)
            <img src="{{ public_path($agreement->behaf_hariss_sign) }}" height="40">
            @endif 
            </span>
        </td>
        <td style="padding-top:6px;">
            Signature:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:78%; vertical-align:middle">
            @if($agreement->behaf_reciver_sign)
            <img src="{{ public_path($agreement->behaf_reciver_sign) }}" height="40">
            @endif
            </span>
        </td>
    </tr>

    <tr>
        <td style="padding-top:6px;">
            Date:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:85%;">
               {{ $agreement->behaf_hariss_date ? \Carbon\Carbon::parse($agreement->behaf_hariss_date)->format('d-m-Y')  : '-' }}
            </span>
        </td>
        <td style="padding-top:6px;">
            Date:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:85%;">
                {{ $agreement->behaf_reciver_date ? \Carbon\Carbon::parse($agreement->behaf_reciver_date)->format('d-m-Y')  : '-' }}
            </span>
        </td>
    </tr>

</table>

<!-- ===================== WITNESSES ===================== -->

<table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:12px;">

    <tr>
        <td style="padding-bottom:6px;">
            <strong>In the Presence Of:</strong>
        </td>
    </tr>

    <!-- 1 -->
    <tr>
        <td>
            1. Sales Executive:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:162px;">
                {{ $agreement->presence_sales_name ?? '' }}
            </span>

            &nbsp;&nbsp;&nbsp;

            Name & Contact No:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:110px;">
                {{ $agreement->presence_sales_contact ?? '' }}
            </span>

            &nbsp;&nbsp;&nbsp;

            Signature:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:120px; height:40px; vertical-align:middle;">
                @if($agreement->presence_sign)
                    <img src="{{ public_path($agreement->presence_sign) }}" height="35">
                @endif
            </span>
        </td>
    </tr>

    <!-- 2 -->
    <tr>
        <td style="padding-top:8px;">
            2. LC Officer/Agent:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:158px;">
                {{ $agreement->presence_lc_name ?? '' }}
            </span>

            &nbsp;&nbsp;&nbsp;

            Name & Contact No:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:110px;">
                {{ $agreement->presence_lc_contact ?? '' }}
            </span>

            &nbsp;&nbsp;&nbsp;

            Signature:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:120px; height:40px; vertical-align:middle;">
                @if($agreement->presence_lc_sign)
                    <img src="{{ public_path($agreement->presence_lc_sign) }}" height="35">
                @endif
            </span>
        </td>
    </tr>

    <!-- 3 -->
    <tr>
        <td style="padding-top:8px;">
            3. Land Lord:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:190px;">
                {{ $agreement->presence_landloard_name ?? '' }}
            </span>

            &nbsp;&nbsp;&nbsp;

            Name & Contact No:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:110px;">
                {{ $agreement->presence_landloard_contact ?? '' }}
            </span>

            &nbsp;&nbsp;&nbsp;

            Signature:
            <span style="display:inline-block; border-bottom:1px dotted #000; width:120px; height:40px; vertical-align:middle;">
                @if($agreement->presence_landloard_sign)
                    <img src="{{ public_path($agreement->presence_landloard_sign) }}" height="35">
                @endif
            </span>
        </td>
    </tr>

</table>

</div>


</body>

</html>