<?php
defined('MOODLE_INTERNAL') || die();
/**
 * DOT Rwanda – custom login layout.
 * Replaces Boost's login layout entirely so we control the full page.
 */
$hassidepre  = $PAGE->blocks->region_has_content('side-pre',  $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body <?php echo $OUTPUT->body_attributes('dot-login-page'); ?>>

<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div class="dot-login-wrapper">

    <!-- Left panel: brand / mission -->
    <div class="dot-login-brand">
        <div class="dot-login-brand-inner">

            <div class="dot-brand-logo">
                <?php
                // Use theme logo if uploaded, otherwise fall back to text mark
                $logourl = $OUTPUT->get_logo_url(null, 80);
                if ($logourl): ?>
                    <img src="<?php echo $logourl; ?>" alt="DOT Rwanda" class="dot-logo-img">
                <?php else: ?>
                    <div class="dot-logo-text">
                        <span class="dot-logo-dot">DOT</span>
                        <span class="dot-logo-rwanda">Rwanda</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dot-brand-tagline">
                <h1>Digital Opportunity Trust Rwanda</h1>
                <p>Empowering Rwandan Youth for the Digital Economy. Youth  with the digital skills, leadership, and entrepreneurial mindset to thrive in a connected world.</p>
            </div>

            <div class="dot-brand-stats">
                <div class="dot-stat">
                    <span class="dot-stat-number">120K+</span>
                    <span class="dot-stat-label">Youth Peers</span>
                </div>
                <div class="dot-stat">
                    <span class="dot-stat-number">1,200</span>
                    <span class="dot-stat-label">Youth Leaders</span>
                </div>
                
            </div>

            <div class="dot-brand-partners">
                <span>Digital Skills for Employability</span>
                
            </div>

        </div>

        <!-- Decorative geometric pattern -->
        <div class="dot-brand-pattern" aria-hidden="true">
            <svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="350" cy="50"  r="120" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
                <circle cx="350" cy="50"  r="80"  stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
                <circle cx="350" cy="50"  r="40"  stroke="rgba(255,255,255,0.12)" stroke-width="1"/>
                <circle cx="60"  cy="340" r="100" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                <circle cx="60"  cy="340" r="60"  stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
                <line x1="0" y1="200" x2="400" y2="200" stroke="rgba(255,255,255,0.04)" stroke-width="0.5"/>
                <line x1="200" y1="0"  x2="200" y2="400" stroke="rgba(255,255,255,0.04)" stroke-width="0.5"/>
            </svg>
        </div>
    </div>

    <!-- Right panel: login form -->
    <div class="dot-login-form-panel">
        <div class="dot-login-form-inner">

      


            <!-- Standard Moodle login form -->
            <?php echo $OUTPUT->main_content(); ?>

            <div class="dot-form-footer">
                <a href="<?php echo new moodle_url('/login/forgot_password.php'); ?>">
                    <?php print_string('forgotten'); ?>
                </a>
            </div>

        </div>
    </div>
</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>
