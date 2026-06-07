"""Publishes the BYD Atto 2 vs Toyota Ebella comparison article to WordPress."""
import requests

WP_URL = "https://nepaligarage.com"
AUTH = ("admin", "mc3n wNfB JKTy LCiX 6BAj GQUm")

CONTENT = """
<div class="ng-tldr-box" style="background:#F0F4FF;border-left:4px solid #1B2A6B;padding:20px 24px;margin:0 0 32px;border-radius:6px;">
<strong style="color:#1B2A6B;font-size:13px;letter-spacing:1px;text-transform:uppercase;">TL;DR — Key Takeaways</strong>
<ul style="margin:12px 0 0;padding-left:20px;color:#374151;line-height:1.9;">
<li><strong>BYD Atto 2</strong> is available now in Nepal at NPR 45,95,000 via Cimex Nepal (confirmed official price).</li>
<li><strong>Toyota Urban Cruiser Ebella</strong> has stronger specs on paper — 61 kWh, 543 km range, 128 kW, 7 airbags — but has <strong>no confirmed Nepal launch, price, or warranty</strong> as of June 2026.</li>
<li>Ground clearance is <strong>unconfirmed for both vehicles</strong> — do not rely on figures circulating online. Verify at the showroom.</li>
<li>Need an EV now: BYD Atto 2. Can wait for Toyota: hold until Nepal launch is confirmed.</li>
<li>All specs sourced from official manufacturer and dealer pages. Every unconfirmed claim is labelled.</li>
</ul>
</div>

<p>Two electric SUVs dominate Nepal EV conversations in 2026: the <strong>BYD Atto 2</strong>, already available through Cimex Nepal, and the <strong>Toyota Urban Cruiser Ebella</strong>, officially listed on Toyota India but not yet on Toyota Nepal's website. This comparison uses only verified official sources — every unconfirmed claim is explicitly labelled.</p>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
<h2>Nepal Market Status</h2>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<thead><tr style="background:#1B2A6B;color:#fff;">
<th style="padding:12px 16px;text-align:left;">Parameter</th>
<th style="padding:12px 16px;text-align:left;">BYD Atto 2</th>
<th style="padding:12px 16px;text-align:left;">Toyota Urban Cruiser Ebella E3</th>
</tr></thead>
<tbody>
<tr style="border-bottom:1px solid #E5E7EB;">
<td style="padding:12px 16px;font-weight:600;">Nepal availability</td>
<td style="padding:12px 16px;"><span style="background:#DCFCE7;color:#166534;padding:3px 8px;border-radius:4px;font-size:13px;font-weight:700;">&#10003; Confirmed</span><br>BYD Nepal + Cimex Nepal official pages</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:3px 8px;border-radius:4px;font-size:13px;font-weight:700;">&#9888; Verify</span><br>Not found on Toyota Nepal website (June 2026). Listed on Toyota Bharat India only.</td>
</tr>
<tr style="border-bottom:1px solid #E5E7EB;background:#F9FAFB;">
<td style="padding:12px 16px;font-weight:600;">Nepal on-road price</td>
<td style="padding:12px 16px;"><span style="background:#DCFCE7;color:#166534;padding:3px 8px;border-radius:4px;font-size:13px;font-weight:700;">&#10003; Official</span><br><strong>NPR 45,95,000</strong> per Cimex Nepal price list</td>
<td style="padding:12px 16px;"><span style="background:#FEE2E2;color:#991B1B;padding:3px 8px;border-radius:4px;font-size:13px;font-weight:700;">&#10007; Not confirmed</span><br>No official Nepal price. Do not rely on NPR 52&#8211;58 lakh figures online.</td>
</tr>
</tbody>
</table>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
<h2>Powertrain and Battery</h2>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<thead><tr style="background:#1B2A6B;color:#fff;">
<th style="padding:12px 16px;text-align:left;">Spec</th>
<th style="padding:12px 16px;text-align:left;">BYD Atto 2</th>
<th style="padding:12px 16px;text-align:left;">Toyota Ebella E3</th>
<th style="padding:12px 16px;text-align:left;">Confidence</th>
</tr></thead>
<tbody>
<tr style="border-bottom:1px solid #E5E7EB;">
<td style="padding:12px 16px;font-weight:600;">Battery capacity</td>
<td style="padding:12px 16px;">51.13 kWh</td><td style="padding:12px 16px;">61 kWh</td>
<td style="padding:12px 16px;"><span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Official &#8212; both</span></td>
</tr>
<tr style="border-bottom:1px solid #E5E7EB;background:#F9FAFB;">
<td style="padding:12px 16px;font-weight:600;">Battery type</td>
<td style="padding:12px 16px;">LFP Blade Battery (CTB)</td><td style="padding:12px 16px;">LFP</td>
<td style="padding:12px 16px;"><span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Official &#8212; both</span></td>
</tr>
<tr style="border-bottom:1px solid #E5E7EB;">
<td style="padding:12px 16px;font-weight:600;">Motor power</td>
<td style="padding:12px 16px;">100 kW</td><td style="padding:12px 16px;">128 kW</td>
<td style="padding:12px 16px;"><span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Official &#8212; both</span></td>
</tr>
<tr style="border-bottom:1px solid #E5E7EB;background:#F9FAFB;">
<td style="padding:12px 16px;font-weight:600;">Motor torque</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:2px 7px;border-radius:4px;font-size:12px;">Verify</span> Not on BYD Nepal page</td>
<td style="padding:12px 16px;">193 Nm</td>
<td style="padding:12px 16px;"><span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Toyota official</span></td>
</tr>
<tr style="border-bottom:1px solid #E5E7EB;">
<td style="padding:12px 16px;font-weight:600;">Certified range</td>
<td style="padding:12px 16px;">345 km (WLTP)</td>
<td style="padding:12px 16px;">543 km (AIS 040/ARAI)</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:2px 7px;border-radius:4px;font-size:12px;">Different test cycles &#8212; not directly comparable</span></td>
</tr>
<tr style="border-bottom:1px solid #E5E7EB;background:#F9FAFB;">
<td style="padding:12px 16px;font-weight:600;">Drivetrain</td>
<td style="padding:12px 16px;">FWD</td><td style="padding:12px 16px;">FWD</td>
<td style="padding:12px 16px;"><span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Official &#8212; both</span></td>
</tr>
</tbody>
</table>
<p style="background:#FEF3C7;border-left:3px solid #D97706;padding:12px 16px;border-radius:4px;margin:8px 0 24px;font-size:14px;"><strong>Range note:</strong> WLTP (BYD) and AIS 040/ARAI (Toyota) are different test cycles. Real-world range on Nepal roads &#8212; with altitude, cold weather, and AC use &#8212; will be lower than both figures.</p>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
<h2>Dimensions</h2>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<thead><tr style="background:#1B2A6B;color:#fff;">
<th style="padding:12px 16px;text-align:left;">Dimension</th>
<th style="padding:12px 16px;text-align:left;">BYD Atto 2</th>
<th style="padding:12px 16px;text-align:left;">Toyota Ebella E3</th>
</tr></thead>
<tbody>
<tr style="border-bottom:1px solid #E5E7EB;">
<td style="padding:12px 16px;font-weight:600;">Length / Width / Height</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:2px 7px;border-radius:4px;font-size:12px;">Verify</span> Not confirmed on official Nepal page</td>
<td style="padding:12px 16px;">4,285 / 1,800 / 1,640 mm <span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Official</span></td>
</tr>
<tr style="border-bottom:1px solid #E5E7EB;background:#F9FAFB;">
<td style="padding:12px 16px;font-weight:600;">Wheelbase</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:2px 7px;border-radius:4px;font-size:12px;">Verify</span></td>
<td style="padding:12px 16px;">2,700 mm <span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Official</span></td>
</tr>
<tr>
<td style="padding:12px 16px;font-weight:600;">Ground clearance</td>
<td style="padding:12px 16px;"><span style="background:#FEE2E2;color:#991B1B;padding:2px 7px;border-radius:4px;font-size:12px;">Unconfirmed</span> Multiple conflicting figures online</td>
<td style="padding:12px 16px;"><span style="background:#FEE2E2;color:#991B1B;padding:2px 7px;border-radius:4px;font-size:12px;">Unconfirmed</span></td>
</tr>
</tbody>
</table>
<p style="background:#FEE2E2;border-left:3px solid #DC2626;padding:12px 16px;border-radius:4px;margin:8px 0 24px;font-size:14px;"><strong>Ground clearance warning:</strong> Multiple figures circulate online for both vehicles. None confirmed from official Nepal dealer brochures. Measure physically at the showroom &#8212; this matters significantly on Nepal roads.</p>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
<h2>Safety</h2>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<thead><tr style="background:#1B2A6B;color:#fff;">
<th style="padding:12px 16px;text-align:left;">Safety feature</th>
<th style="padding:12px 16px;text-align:left;">BYD Atto 2</th>
<th style="padding:12px 16px;text-align:left;">Toyota Ebella E3</th>
</tr></thead>
<tbody>
<tr style="border-bottom:1px solid #E5E7EB;">
<td style="padding:12px 16px;font-weight:600;">Airbags</td>
<td style="padding:12px 16px;">6 airbags (Cimex Nepal confirmed)</td>
<td style="padding:12px 16px;">7 airbags (Toyota Bharat confirmed)</td>
</tr>
<tr style="border-bottom:1px solid #E5E7EB;background:#F9FAFB;">
<td style="padding:12px 16px;font-weight:600;">ADAS</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:2px 7px;border-radius:4px;font-size:12px;">Verify from Nepal brochure</span></td>
<td style="padding:12px 16px;">Level 2 ADAS (top variant); PCS; BSM; RCTA; Hill Hold; ABS/EBD/VSC</td>
</tr>
<tr>
<td style="padding:12px 16px;font-weight:600;">360&#176; camera</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:2px 7px;border-radius:4px;font-size:12px;">Verify</span></td>
<td style="padding:12px 16px;">Yes (Toyota Bharat confirmed)</td>
</tr>
</tbody>
</table>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
<h2>Charging</h2>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<thead><tr style="background:#1B2A6B;color:#fff;">
<th style="padding:12px 16px;text-align:left;">Charging detail</th>
<th style="padding:12px 16px;text-align:left;">BYD Atto 2</th>
<th style="padding:12px 16px;text-align:left;">Toyota Ebella E3</th>
</tr></thead>
<tbody>
<tr style="border-bottom:1px solid #E5E7EB;">
<td style="padding:12px 16px;font-weight:600;">DC fast charge</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:2px 7px;border-radius:4px;font-size:12px;">Verify from Nepal brochure</span></td>
<td style="padding:12px 16px;">CCS2; 10&#8211;80% in 45 min <span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Official</span></td>
</tr>
<tr style="background:#F9FAFB;">
<td style="padding:12px 16px;font-weight:600;">AC wallbox</td>
<td style="padding:12px 16px;"><span style="background:#FEF3C7;color:#92400E;padding:2px 7px;border-radius:4px;font-size:12px;">Verify</span></td>
<td style="padding:12px 16px;">7.2 kW compatible <span style="background:#DCFCE7;color:#166534;padding:2px 7px;border-radius:4px;font-size:12px;">Official</span></td>
</tr>
</tbody>
</table>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
<h2>Nepal Buyer Verdict</h2>
<div style="background:linear-gradient(135deg,#EFF6FF,#F0FDF4);border:1px solid #BFDBFE;border-radius:8px;padding:24px;margin:16px 0;">
<p><strong>Buy BYD Atto 2 if</strong> you need an EV in Nepal today. NPR 45,95,000 is confirmed. The car is available now through Cimex Nepal. The 51.13 kWh Blade Battery with 345 km WLTP range is sufficient for Kathmandu valley use and most Nepal highway routes.</p>
<p><strong>Wait for Toyota Urban Cruiser Ebella if</strong> you value Toyota brand and the larger 61 kWh battery with 128 kW motor. But wait until Toyota Nepal officially confirms the price, warranty, service network, and parts availability &#8212; do not commit before then.</p>
<p><strong>Ask every dealer in writing:</strong> ground clearance (unladen and laden), battery warranty SOH threshold, charging connector type, home charger cost, spare parts timeline, and 5-year service package cost.</p>
</div>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
<h2>Frequently Asked Questions</h2>

<h3>What is the official price of BYD Atto 2 in Nepal?</h3>
<p>NPR 45,95,000 as listed on the Cimex Nepal official price list (verified June 2026). Cimex is the official BYD distributor in Nepal.</p>

<h3>Has Toyota Urban Cruiser Ebella launched in Nepal?</h3>
<p>As of June 2026, the Toyota Urban Cruiser Ebella is not confirmed on Toyota Nepal's official website. It is listed on Toyota Bharat (India) only. Do not rely on expected price figures from social media.</p>

<h3>Which has longer range &#8212; BYD Atto 2 or Toyota Ebella?</h3>
<p>Toyota Ebella lists 543 km (AIS 040/ARAI). BYD Atto 2 lists 345 km (WLTP). These are different test cycles and cannot be compared directly. Real-world Nepal range will be lower for both.</p>

<h3>What is the ground clearance of BYD Atto 2 in Nepal?</h3>
<p>Not confirmed in official Nepal sources. Multiple figures circulate online but none are verified from an official Nepal dealer brochure. Measure physically at the showroom before purchase.</p>

<h3>Which EV should I buy in Nepal in 2026 &#8212; BYD Atto 2 or Toyota Ebella?</h3>
<p>If you need an EV now with a confirmed price: BYD Atto 2 at NPR 45,95,000. If you prefer Toyota and can wait: hold until Toyota Nepal officially announces the Ebella with confirmed price, warranty, and service terms.</p>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
<h2>Sources and References</h2>
<p style="font-size:13px;color:#6B7280;">All facts sourced from official manufacturer and dealer pages. Click any link to view the original document.</p>
<ol style="line-height:2;font-size:14px;">
<li><a href="https://www.toyotabharat.com/showroom/urbancruiser-ebella/" target="_blank" rel="noopener noreferrer">Toyota Bharat &#8212; Urban Cruiser Ebella official product page</a> &#8212; battery, motor, range, dimensions, safety, charging, warranty</li>
<li><a href="https://www.toyotabharat.com/documents/brochures/e-brochure-urbancruiser-ebella.pdf" target="_blank" rel="noopener noreferrer">Toyota Bharat &#8212; Urban Cruiser Ebella official e-brochure PDF</a></li>
<li><a href="https://www.byd.com/np/car/atto2" target="_blank" rel="noopener noreferrer">BYD Nepal &#8212; Atto 2 official product page</a> &#8212; range, battery, power, platform, Blade Battery, CTB technology</li>
<li><a href="https://cimex.com.np/price-list" target="_blank" rel="noopener noreferrer">Cimex Nepal &#8212; official BYD distributor price list</a> &#8212; Nepal retail price NPR 45,95,000</li>
<li><a href="https://cimex.com.np/atto2" target="_blank" rel="noopener noreferrer">Cimex Nepal &#8212; BYD Atto 2 dealer page</a> &#8212; 6 airbags confirmed</li>
<li><a href="https://www.toyota.com.np/en.html" target="_blank" rel="noopener noreferrer">Toyota Nepal official website</a> &#8212; Ebella not visible June 2026</li>
</ol>
<p style="font-size:13px;color:#6B7280;margin-top:16px;font-style:italic;">For final purchase decisions, request the official Nepal invoice, warranty booklet, and current road-tax fees from your dealer.</p>

<!-- NepaliGarage Structured Data -->
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Article","headline":"BYD Atto 2 vs Toyota Urban Cruiser Ebella — Nepal EV Comparison 2026","description":"Verified source comparison of BYD Atto 2 and Toyota Urban Cruiser Ebella for Nepal buyers. Confirmed specs, official Nepal price, source confidence labels.","datePublished":"2026-06-07","dateModified":"2026-06-07","publisher":{"@type":"Organization","name":"NepaliGarage","url":"https://nepaligarage.com"},"author":{"@type":"Organization","name":"NepaliGarage Editorial"},"inLanguage":"en-NP"}</script>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"What is the official price of BYD Atto 2 in Nepal?","acceptedAnswer":{"@type":"Answer","text":"NPR 45,95,000 as listed on the Cimex Nepal official price list (verified June 2026)."}},{"@type":"Question","name":"Has Toyota Urban Cruiser Ebella launched in Nepal?","acceptedAnswer":{"@type":"Answer","text":"As of June 2026, the Toyota Urban Cruiser Ebella is not confirmed on Toyota Nepal official website. Listed on Toyota Bharat India only."}},{"@type":"Question","name":"What is the ground clearance of BYD Atto 2 in Nepal?","acceptedAnswer":{"@type":"Answer","text":"Not confirmed in official Nepal sources. Verify at the showroom before purchase."}},{"@type":"Question","name":"Which EV should I buy in Nepal in 2026?","acceptedAnswer":{"@type":"Answer","text":"BYD Atto 2 at NPR 45,95,000 if you need an EV now. Wait for Toyota Ebella only after Toyota Nepal officially confirms price, warranty, and service terms."}}]}</script>
"""

r = requests.post(
    f"{WP_URL}/wp-json/wp/v2/posts",
    json={
        "title": "BYD Atto 2 vs Toyota Urban Cruiser Ebella — Nepal EV Comparison 2026",
        "slug": "byd-atto-2-vs-toyota-urban-cruiser-ebella-nepal-2026",
        "content": CONTENT,
        "excerpt": "Verified-source Nepal EV comparison: BYD Atto 2 (NPR 45,95,000 — confirmed Cimex Nepal) vs Toyota Urban Cruiser Ebella (not yet officially launched in Nepal as of June 2026). Official specs only, with confidence labels on every claim.",
        "status": "draft",
    },
    auth=AUTH,
    verify=False,
    timeout=30
)

if r.status_code in (200, 201):
    p = r.json()
    print(f"SUCCESS")
    print(f"Post ID: {p['id']}")
    print(f"Edit: {WP_URL}/wp-admin/post.php?post={p['id']}&action=edit")
    print(f"Status: {p['status']}")
else:
    print(f"Error {r.status_code}: {r.text[:400]}")
