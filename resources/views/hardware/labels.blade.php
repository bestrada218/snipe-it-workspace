<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Labels</title>

</head>
<body>

<?php
$settings->labels_width = $settings->labels_width - $settings->labels_display_sgutter;
$settings->labels_height = $settings->labels_height - $settings->labels_display_bgutter;
// Leave space on bottom for 1D barcode if necessary
$qr_size = ($settings->alt_barcode_enabled=='1') && ($settings->label2_1d_type!='') ? $settings->labels_height - .3 : $settings->labels_height - 0.1;
?>

<style>
    body {
        font-family: arial, helvetica, sans-serif;
        width: {{ $settings->labels_pagewidth }}in;
        height: {{ $settings->labels_pageheight }}in;
        margin: {{ $settings->labels_pmargin_top }}in {{ $settings->labels_pmargin_right }}in {{ $settings->labels_pmargin_bottom }}in {{ $settings->labels_pmargin_left }}in;
        font-size: {{ $settings->labels_fontsize }}pt;
    }
    /* Each label is a box: optional QR on the left, text on top and the
       1D barcode in its own strip at the bottom, so nothing overlaps. */
    .label {
        box-sizing: border-box;
        width: {{ $settings->labels_width }}in;
        height: {{ $settings->labels_height }}in;
        padding: .03in .04in;
        margin-right: {{ $settings->labels_display_sgutter }}in; /* the gutter */
        margin-bottom: {{ $settings->labels_display_bgutter }}in;
        display: inline-flex;
        gap: .06in;
        vertical-align: top;
        overflow: hidden;
    }
    .page-break  {
        page-break-after:always;
    }
    div.qr_img {
        flex: none;
        width: {{ $qr_size }}in;
        height: {{ $qr_size }}in;
        overflow: hidden;
    }
    img.qr_img {
        display: block;
        width: 100%;
        height: 100%;
    }
    .label-body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .label-text {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        line-height: 1.15;
    }
    .label-line {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .label-tag {
        font-size: 1.25em;
        font-weight: 700;
        letter-spacing: .02em;
    }
    div.label-logo {
        float: right;
        margin-left: .04in;
    }
    img.label-logo {
        height: 0.4in;
    }
    div.barcode_container {
        flex: none;
        height: .26in;
        margin-top: .02in;
    }
    img.barcode {
        display: block;
        width: 100%;
        height: 100%;
    }
    .next-padding {
        margin: {{ $settings->labels_pmargin_top }}in {{ $settings->labels_pmargin_right }}in {{ $settings->labels_pmargin_bottom }}in {{ $settings->labels_pmargin_left }}in;
    }
    @media print {
        .noprint {
            display: none !important;
        }
        .next-padding {
            margin: {{ $settings->labels_pmargin_top }}in {{ $settings->labels_pmargin_right }}in {{ $settings->labels_pmargin_bottom }}in {{ $settings->labels_pmargin_left }}in;
            font-size: 0;
        }
    }
    @media screen {
        .label {
            outline: .02in black solid; /* outline doesn't occupy space like border does */
        }
        .noprint {
            font-size: 13px;
            padding-bottom: 15px;
        }
    }
    @if ($snipeSettings->custom_css)
        {!! $snipeSettings->show_custom_css() !!}
    @endif
</style>

@foreach ($assets as $asset)
    <?php $count++; ?>
    <div class="label">

        @if ($settings->qr_code=='1' && isset($barcode_urls[$asset->id]['qr']))
            <div class="qr_img">
                <img src="{{ $barcode_urls[$asset->id]['qr'] }}" class="qr_img" alt="">
            </div>
        @endif

        <div class="label-body">
            <div class="label-text">
                @if ($settings->label_logo)
                    <div class="label-logo">
                        <img class="label-logo" src="{{ Storage::disk('public')->url('').e($snipeSettings->label_logo) }}" alt="">
                    </div>
                @endif
                {{-- The asset tag is what people look for: first and in bold. --}}
                @if (($settings->labels_display_tag=='1') && ($asset->asset_tag!=''))
                    <div class="label-line label-tag">{{ $asset->asset_tag }}</div>
                @endif
                @if ($settings->qr_text!='')
                    <div class="label-line"><strong>{{ $settings->qr_text }}</strong></div>
                @endif
                @if (($settings->labels_display_name=='1') && ($asset->name!=''))
                    <div class="label-line">N: {{ $asset->name }}</div>
                @endif
                @if (($settings->labels_display_model=='1') && ($asset->model?->name!=''))
                    <div class="label-line">M: {{ $asset->model->name }} {{ $asset->model->model_number }}</div>
                @endif
                @if (($settings->labels_display_serial=='1') && ($asset->serial!=''))
                    <div class="label-line">S: {{ $asset->serial }}</div>
                @endif
                {{-- Assets without a company print the organisation (site) name. --}}
                @if (($settings->labels_display_company_name=='1') && ($asset->company?->name ?: $snipeSettings->site_name))
                    <div class="label-line">C: {{ $asset->company?->name ?: $snipeSettings->site_name }}</div>
                @endif
            </div>

            @if ((($settings->alt_barcode_enabled=='1') && $settings->label2_1d_type!='') && isset($barcode_urls[$asset->id]['barcode']))
                <div class="barcode_container">
                    <img src="{{ $barcode_urls[$asset->id]['barcode'] }}" class="barcode" alt="">
                </div>
            @endif
        </div>

    </div>

    @if ($settings->labels_per_page > 0 && ($count % $settings->labels_per_page == 0) && $count != count($assets))
    <div class="page-break"></div>
    <div class="next-padding">&nbsp;</div>
    @endif

@endforeach


</body>
</html>
