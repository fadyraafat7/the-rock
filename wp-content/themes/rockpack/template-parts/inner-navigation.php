<?php
/** Shared website navigation. @package Rockpack */
defined( 'ABSPATH' ) || exit;
$assets = get_template_directory_uri() . '/assets/images/certifications/';
$dropdowns = rockpack_collection('navigation_groups');
?>
<nav class="rp-inner-nav" aria-label="Main navigation">
	<script>document.currentScript.parentElement.classList.add('rp-inner-nav--enhanced');</script>
	<a class="rp-inner-nav__brand" href="<?php echo esc_url( rp_option('shared_link_home', home_url( '/' ) ) ); ?>" aria-label="The Rock Pack home"><?php if ( rp_option('nav_brand_image', $assets . 'logo.png') ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  rp_option('nav_brand_image', $assets . 'logo.png')  )); ?>" alt="The Rock Pack" width="80" height="91"><?php endif; ?></a>
	<button class="rp-inner-nav__toggle" type="button" aria-controls="rp-navigation-drawer" aria-expanded="false" aria-label="Open navigation"><span></span><span></span><span></span></button>
	<div class="rp-inner-nav__backdrop" aria-hidden="true"></div>
	<div class="rp-inner-nav__drawer" id="rp-navigation-drawer">
		<div class="rp-inner-nav__drawer-head"><span id="rp-mobile-menu-title"><?php esc_html_e('Menu','rockpack'); ?></span><button class="rp-inner-nav__close" type="button" aria-label="<?php esc_attr_e('Close navigation','rockpack'); ?>"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
        <form class="rp-inner-nav__drawer-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <label class="rp-header-search__label" for="rp-drawer-search-input"><?php esc_html_e( 'Search the website', 'rockpack' ); ?></label>
            <input id="rp-drawer-search-input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search the website', 'rockpack' ); ?>" required>
            <button type="submit" aria-label="<?php esc_attr_e( 'Submit search', 'rockpack' ); ?>"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg></button>
        </form>
	<ul class="rp-inner-nav__links" id="rp-inner-menu">
		<li><?php if ( (rp_option('shared_link_home', home_url( '/' ) )) && (rp_option('nav_label_home','Home')) ) : ?><a href="<?php echo esc_url( rp_option('shared_link_home', home_url( '/' ) ) ); ?>" <?php if ( is_front_page() ) echo 'aria-current="page"'; ?>><?php echo esc_html(rp_option('nav_label_home','Home')); ?></a><?php endif; ?></li>
		<?php foreach ( $dropdowns as $group_index => $dropdown ) : ?>
		<?php $key=sanitize_key($dropdown['menu_key'])?:('menu-'.$group_index); $key.='-'.$group_index; if(empty($dropdown['title'])) continue; ?>
		<li><details class="rp-inner-nav__dropdown rp-inner-nav__dropdown--<?php echo esc_attr( sanitize_key($dropdown['menu_key']) ); ?>">
			<summary aria-controls="rp-submenu-<?php echo esc_attr( $key ); ?>"><span class="rp-inner-nav__dropdown-label"><?php echo esc_html( $dropdown['title'] ); ?></span><svg class="rp-inner-nav__chevron" width="10.4" height="6" viewBox="0 0 10.4 6" fill="none" aria-hidden="true"><path d="m1 1 4.2 4L9.4 1" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg></summary>
			<ul id="rp-submenu-<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( $dropdown['title'] ); ?>">
			<?php foreach ( $dropdown['items'] as $item ) :
                $url=$item['url'];
                $current=is_singular() && untrailingslashit($url)===untrailingslashit(get_permalink());
			?>
				<li><?php if ( ($url) && ($item['title']) ) : ?><a href="<?php echo esc_url( $url ); ?>" <?php if ( $current ) echo 'aria-current="page"'; ?>><?php echo esc_html( $item['title'] ); ?></a><?php endif; ?></li>
			<?php endforeach; ?>
			</ul>
		</details></li>
		<?php endforeach; ?>
		<li><?php if ( (rp_option('shared_link_contact_us', home_url( '/contact-us/' ) )) && (rp_option('nav_label_contact_us','Contact Us')) ) : ?><a href="<?php echo esc_url( rp_option('shared_link_contact_us', home_url( '/contact-us/' ) ) ); ?>" <?php if ( is_page( 'contact-us' ) || is_page_template( 'page-contact-us.php' ) ) echo 'aria-current="page"'; ?>><?php echo esc_html(rp_option('nav_label_contact_us','Contact Us')); ?></a><?php endif; ?></li>
	</ul>
		<div class="rp-inner-nav__drawer-footer">
			<?php if ( rp_option('shared_link_contact_us_contact_form', home_url('/contact-us/#contact-form')) && rp_option('nav_label_get_started','Get Started') ) : ?><a class="rp-inner-nav__cta rp-inner-nav__mobile-cta" href="<?php echo esc_url(rp_option('shared_link_contact_us_contact_form', home_url('/contact-us/#contact-form'))); ?>"><?php echo esc_html(rp_option('nav_label_get_started','Get Started')); ?> <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
		</div>
	</div>
	<div class="rp-inner-nav__actions">
        <form class="rp-header-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <div class="rp-header-search__field" id="rp-header-search-field" hidden>
                <label class="rp-header-search__label" for="rp-search-input"><?php echo esc_html( rp_option( 'nav_search_prompt', 'What are you looking for?' ) ); ?></label>
                <input id="rp-search-input" type="search" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php echo esc_attr( rp_option( 'nav_search_prompt', 'What are you looking for?' ) ); ?>" required disabled hx-get="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" hx-vals='{"action":"rockpack_search_preview"}' hx-trigger="input changed delay:400ms, search" hx-target="#rp-search-preview" hx-sync="this:replace" autocomplete="off" aria-describedby="rp-search-preview">
                <button class="rp-header-search__submit" type="submit" aria-label="<?php esc_attr_e( 'Submit search', 'rockpack' ); ?>"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg></button>
                <div id="rp-search-preview" class="rp-search-preview" aria-live="polite"></div>
            </div>
            <button class="rp-inner-nav__search" type="submit" aria-label="<?php esc_attr_e( 'Search website', 'rockpack' ); ?>" aria-expanded="false" aria-controls="rp-header-search-field" data-submit-label="<?php echo esc_attr( rp_option( 'nav_label_search', 'Search' ) ); ?>" data-close-label="<?php esc_attr_e( 'Close search', 'rockpack' ); ?>"><svg class="rp-header-search__open-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg><svg class="rp-header-search__close-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </form>
        <?php if ( (rp_option('shared_link_contact_us_contact_form', home_url( '/contact-us/#contact-form' ) )) && (rp_option('nav_label_get_started','Get Started')) ) : ?><a class="rp-inner-nav__cta" href="<?php echo esc_url( rp_option('shared_link_contact_us_contact_form', home_url( '/contact-us/#contact-form' ) ) ); ?>"><?php echo esc_html(rp_option('nav_label_get_started','Get Started')); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
    </div>
</nav>
