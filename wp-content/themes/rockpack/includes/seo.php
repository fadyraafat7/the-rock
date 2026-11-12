<?php
/** SEO: meta tags, Open Graph, Twitter Cards, JSON-LD, and page titles. */
defined( 'ABSPATH' ) || exit;

/** Approved public brand, shared by metadata and dynamically rendered pages. */
function rockpack_brand_name() { return 'The Rock Pack'; }
add_filter( 'option_blogname', 'rockpack_brand_name' );
add_filter( 'portable_seo_organization_name', 'rockpack_brand_name' );
add_filter( 'pre_get_document_title', static function ( $title ) {
    return str_replace( 'The-Rock', rockpack_brand_name(), $title );
}, 30 );

// Retain the legacy implementation when Portable SEO is not installed.
if ( ! class_exists( 'Portable_SEO' ) ) {
// ── Meta Description meta box ─────────────────────────────────────────────────

/**
 * Add a "SEO" meta box to pages so editors can set a per-page description.
 * Stored as post meta _rp_seo_description.
 */
add_action( 'add_meta_boxes', function ( $post_type ) {
	if ( ! in_array( $post_type, array( 'page', 'post', 'rp_job' ), true ) ) return;
	add_meta_box(
		'rp_seo_meta',
		__( 'SEO', 'rockpack' ),
		'rp_seo_meta_box_cb',
		$post_type,
		'normal',
		'high'
	);
} );

function rp_seo_meta_box_cb( $post ) {
	$title = get_post_meta( $post->ID, '_rp_seo_title', true );
	$desc  = get_post_meta( $post->ID, '_rp_seo_description', true );
	$img   = get_post_meta( $post->ID, '_rp_seo_og_image', true );
    $noindex = get_post_meta( $post->ID, '_rp_seo_noindex', true );
	wp_nonce_field( 'rp_seo_save_' . $post->ID, 'rp_seo_nonce' );
	?>
	<table class="form-table rp-seo-table" style="margin:0">
		<tr>
			<th scope="row" style="padding:8px 10px 8px 0;width:160px">
				<label for="rp_seo_title" style="font-weight:600">SEO Title</label>
			</th>
			<td style="padding:8px 0">
				<input id="rp_seo_title" name="rp_seo_title" type="text"
					style="width:100%;max-width:600px"
					value="<?php echo esc_attr( $title ); ?>"
					placeholder="Leave empty to use the page title." />
				<p class="description">
					Overrides the browser tab title and search result title. Leave empty to use the default "Page Name | The Rock Pack".
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row" style="padding:8px 10px 8px 0;width:160px">
				<label for="rp_seo_desc" style="font-weight:600">Meta Description</label>
			</th>
			<td style="padding:8px 0">
				<textarea id="rp_seo_desc" name="rp_seo_description" rows="3"
					style="width:100%;max-width:600px"
					placeholder="150–160 characters. Leave empty to use the site default."
					maxlength="320"><?php echo esc_textarea( $desc ); ?></textarea>
				<p class="description">
					Shown in Google search results under the page title. Aim for 150–160 characters.
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row" style="padding:8px 10px 8px 0">
				<label for="rp_seo_og_image" style="font-weight:600">Social Share Image</label>
			</th>
			<td style="padding:8px 0">
				<input id="rp_seo_og_image" name="rp_seo_og_image" type="text"
					style="width:100%;max-width:600px"
					value="<?php echo esc_attr( $img ); ?>"
					placeholder="Leave empty to use the site default OG image." />
				<p class="description">
					Image shown when this page is shared on Facebook, LinkedIn, etc. Use the full URL from the Media Library (1200×630 px recommended).
				</p>
				<button type="button" class="button rp-og-image-select"
					data-target="rp_seo_og_image">
					<?php esc_html_e( 'Choose image', 'rockpack' ); ?>
				</button>
			</td>
		</tr>
        <tr><th scope="row"><label for="rp_seo_noindex">Search indexing</label></th><td><label><input type="checkbox" id="rp_seo_noindex" name="rp_seo_noindex" value="1" <?php checked( $noindex, '1' ); ?>> Discourage search engines from indexing this page and exclude it from sitemaps.</label></td></tr>
	</table>
	<script>
	(function(){
		var btn = document.querySelector('.rp-og-image-select');
		if(!btn) return;
		btn.addEventListener('click',function(){
			var frame = wp.media({title:'Select OG Image',multiple:false,library:{type:'image'}});
			frame.on('select',function(){
				var att = frame.state().get('selection').first().toJSON();
				document.getElementById(btn.dataset.target).value = att.url;
			});
			frame.open();
		});
	})();
	</script>
	<?php
}

add_action( 'save_post', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! in_array( get_post_type( $post_id ), array( 'page', 'post', 'rp_job' ), true ) ) return;
	if ( ! isset( $_POST['rp_seo_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['rp_seo_nonce'], 'rp_seo_save_' . $post_id ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$title = sanitize_text_field( wp_unslash( $_POST['rp_seo_title'] ?? '' ) );
	if ( '' === $title ) {
		delete_post_meta( $post_id, '_rp_seo_title' );
	} else {
		update_post_meta( $post_id, '_rp_seo_title', $title );
	}

	$desc = sanitize_textarea_field( wp_unslash( $_POST['rp_seo_description'] ?? '' ) );
	if ( '' === $desc ) {
		delete_post_meta( $post_id, '_rp_seo_description' );
	} else {
		update_post_meta( $post_id, '_rp_seo_description', $desc );
	}

    if ( isset( $_POST['rp_seo_noindex'] ) && '1' === $_POST['rp_seo_noindex'] ) update_post_meta( $post_id, '_rp_seo_noindex', '1' );
    else delete_post_meta( $post_id, '_rp_seo_noindex' );

	$img = esc_url_raw( wp_unslash( $_POST['rp_seo_og_image'] ?? '' ) );
	if ( '' === $img ) {
		delete_post_meta( $post_id, '_rp_seo_og_image' );
	} else {
		update_post_meta( $post_id, '_rp_seo_og_image', $img );
	}
} );

// ── Helper: get current page description ─────────────────────────────────────

function rp_seo_get_description() {
	// Single pages/posts: use post meta first, then post excerpt.
	if ( is_singular() ) {
		$desc = get_post_meta( get_queried_object_id(), '_rp_seo_description', true );
		if ( $desc ) return $desc;
		$excerpt = get_the_excerpt( get_queried_object_id() );
		if ( $excerpt ) return $excerpt;
	}
    // Section-specific defaults keep descriptions useful when editors leave SEO fields empty.
    $defaults = array(
        'about-us' => 'Discover The Rock Pack, our experience in packaging and material science, and how our manufacturing expertise supports quality and lasting partnerships.',
        'history' => 'Explore The Rock Pack’s history and the milestones behind our experience in packaging, manufacturing, printing and material science across generations.',
        'certifications' => 'Learn about The Rock Pack’s quality systems, certifications and commitment to safe, consistent aseptic packaging production and international standards.',
        'planet' => 'Discover The Rock Pack’s approach to responsible sourcing, environmental considerations and long-term sustainability in our packaging solutions.',
        'product-portfolio' => 'Explore The Rock Pack’s aseptic packaging products, materials and manufacturing capabilities, supporting brands and their needs across diverse markets.',
        'contact-us' => 'Contact The Rock Pack to discuss aseptic packaging and manufacturing capabilities, and find out how our experienced team can support your business.',
    );
    if ( is_singular( 'page' ) ) {
        $slug = get_post_field( 'post_name', get_queried_object_id() );
        if ( isset( $defaults[ $slug ] ) ) return $defaults[ $slug ];
    }
    if ( is_post_type_archive( 'rp_job' ) ) return 'Explore career opportunities at The Rock Pack and learn how our people contribute to quality, innovation and growth in aseptic packaging manufacturing.';
    // Blog index.
	if ( is_home() || is_front_page() ) {
		$desc = is_front_page() ? rp_option( 'seo_home_description', '' ) : '';
		if ( $desc ) return $desc;
        if ( is_home() && ! is_front_page() ) return 'Read the latest news and insights from The Rock Pack on aseptic packaging, manufacturing, production quality and responsible sourcing across our markets.';
	}
	// Fallback to site tagline.
	return rp_option( 'seo_default_description', '' ) ?: get_bloginfo( 'description' ) ?: 'The Rock Pack provides aseptic packaging solutions, combining manufacturing expertise, quality and innovation for brands across Egypt, the Middle East and Africa.';
}

function rp_seo_get_og_image() {
	if ( is_singular() ) {
		$img = get_post_meta( get_queried_object_id(), '_rp_seo_og_image', true );
		if ( $img ) return esc_url( $img );
		if ( has_post_thumbnail() ) {
			return esc_url( get_the_post_thumbnail_url( null, 'large' ) );
		}
	}
	// Site-wide default from theme settings.
	$default = rp_option( 'seo_og_image', '' );
	return esc_url( $default ?: get_template_directory_uri() . '/assets/seo/social-default.jpg' );
}

/** Canonical URLs use WordPress permalinks, never tracking parameters or the Host header. */
function rp_seo_canonical_url() {
    if ( is_404() || is_search() ) return '';
    $paged = max( 1, (int) get_query_var( 'paged' ) );
    if ( is_front_page() && ! is_paged() ) return home_url( '/' );
    if ( is_home() ) {
        $url = is_front_page() ? home_url( '/' ) : rockpack_news_url();
        return $paged > 1 ? add_query_arg( 'page', $paged, $url ) : $url;
    }
    if ( is_singular() ) return wp_get_canonical_url( get_queried_object_id() ) ?: get_permalink( get_queried_object_id() );
    if ( is_post_type_archive() ) {
        $url = get_post_type_archive_link( get_query_var( 'post_type' ) );
        return $url && $paged > 1 ? trailingslashit( $url ) . user_trailingslashit( 'page/' . $paged, 'paged' ) : $url;
    }
    if ( is_category() || is_tag() || is_tax() ) {
        $url = get_term_link( get_queried_object() );
        return is_wp_error( $url ) ? '' : ( $paged > 1 ? get_pagenum_link( $paged ) : $url );
    }
    return is_front_page() ? home_url( '/' ) : '';
}

add_action( 'wp', static function () { remove_action( 'wp_head', 'rel_canonical' ); } );
add_action( 'wp_head', static function () {
    $url = rp_seo_canonical_url();
    if ( $url ) echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
    if ( is_home() || is_post_type_archive( 'rp_job' ) ) {
        global $wp_query;
        $page = max( 1, (int) get_query_var( 'paged' ) );
        $jobs = is_post_type_archive( 'rp_job' );
        $base = $jobs ? rockpack_careers_url() : rockpack_news_url();
        $page_url = static function ( $number ) use ( $base, $jobs ) { return 1 === $number ? $base : ( $jobs ? trailingslashit( $base ) . user_trailingslashit( 'page/' . $number, 'paged' ) : add_query_arg( 'page', $number, $base ) ); };
        if ( $page > 1 ) echo '<link rel="prev" href="' . esc_url( $page_url( $page - 1 ) ) . '">' . "\n";
        if ( $page < (int) $wp_query->max_num_pages ) echo '<link rel="next" href="' . esc_url( $page_url( $page + 1 ) ) . '">' . "\n";
    }
}, 3 );
add_filter( 'wp_robots', static function ( $robots ) {
    if ( is_search() || is_404() || ( is_singular() && '1' === get_post_meta( get_queried_object_id(), '_rp_seo_noindex', true ) ) || ( is_home() && '1' === get_post_meta( (int) get_option( 'page_for_posts' ), '_rp_seo_noindex', true ) ) ) $robots['noindex'] = true;
    return $robots;
} );

// ── wp_head output ────────────────────────────────────────────────────────────

add_action( 'wp_head', 'rp_seo_output_meta_tags', 1 );
function rp_seo_output_meta_tags() {
	$desc     = rp_seo_get_description();
	$og_image = rp_seo_get_og_image();
	$title    = wp_get_document_title();
	$url      = rp_seo_canonical_url();
	$type     = is_singular( 'post' ) ? 'article' : 'website';

	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}

	// Open Graph
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $url ) echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '">' . "\n";
	if ( is_singular( 'post' ) ) echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . "\n";
	if ( $og_image ) {
		echo '<meta property="og:image" content="' . esc_attr( $og_image ) . '">' . "\n";
	}

	// Twitter Card
	echo '<meta name="twitter:card" content="' . ( $og_image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $og_image ) {
		echo '<meta name="twitter:image" content="' . esc_attr( $og_image ) . '">' . "\n";
	}
}

// ── JSON-LD Organization schema ───────────────────────────────────────────────

add_action( 'wp_head', 'rp_seo_output_jsonld', 2 );
function rp_seo_output_jsonld() {
	$name    = get_bloginfo( 'name' );
	$url     = home_url( '/' );
	$logo    = rp_option( 'nav_brand_image', get_template_directory_uri() . '/assets/images/certifications/logo.png' );
	$phone   = rp_option( 'contact_phone', '' );
	$address = rp_option( 'contact_address', '' );
	$email   = rp_option( 'contact_email', '' );

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Organization',
		'name'        => $name,
		'url'         => $url,
		'logo'        => array(
			'@type' => 'ImageObject',
			'url'   => $logo,
		),
	);

	if ( $phone )   $schema['telephone']  = $phone;
	if ( $email )   $schema['email']      = $email;
	if ( $address ) {
		$schema['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address,
		);
	}

	// Add social profiles if set.
	$socials = array();
	foreach ( rockpack_collection( 'footer_social' ) as $row ) {
		if ( ! empty( $row['url'] ) && in_array( wp_parse_url( $row['url'], PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) $socials[] = esc_url_raw( $row['url'] );
	}
	if ( $socials ) $schema['sameAs'] = $socials;

	echo '<script type="application/ld+json">' . "\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP );
	echo "\n" . '</script>' . "\n";
    if ( is_singular( 'post' ) ) {
        $article = array(
            '@context' => 'https://schema.org', '@type' => 'Article',
            'headline' => get_the_title( get_queried_object_id() ),
            'description' => rp_seo_get_description(),
            'mainEntityOfPage' => rp_seo_canonical_url(),
            'datePublished' => get_the_date( DATE_W3C ), 'dateModified' => get_the_modified_date( DATE_W3C ),
            'author' => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', get_post_field( 'post_author', get_queried_object_id() ) ) ),
            'publisher' => array( '@type' => 'Organization', 'name' => $name, 'logo' => $schema['logo'] ),
        );
        if ( rp_seo_get_og_image() ) $article['image'] = rp_seo_get_og_image();
        echo '<script type="application/ld+json">' . wp_json_encode( $article, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n";
    }
}

// ── Page title filter ─────────────────────────────────────────────────────────

/**
 * Home page: "The Rock Pack | Aseptic Packaging Manufacturer – Egypt, MENA & Africa"
 * Inner pages: WordPress builds "Page Name – Site Name" via title-tag support.
 */
add_filter( 'pre_get_document_title', 'rp_seo_document_title' );
function rp_seo_document_title( $title ) {
	// Per-page custom title (from SEO meta box) — overrides everything.
	if ( is_singular() ) {
		$custom = get_post_meta( get_queried_object_id(), '_rp_seo_title', true );
		if ( $custom ) return $custom;
	}
	// Home page: build from site name + tagline setting.
	if ( is_front_page() ) {
		$tagline = rp_option( 'seo_home_title_suffix', 'Aseptic Packaging Manufacturer – Egypt, MENA & Africa' );
		return get_bloginfo( 'name' ) . ' | ' . $tagline;
	}
    $titles = array(
        'about-us' => 'About Our Packaging Expertise', 'history' => 'Our Packaging Manufacturing History',
        'certifications' => 'Packaging Quality and Certifications', 'planet' => 'Responsible Packaging and Our Planet',
        'product-portfolio' => 'Aseptic Packaging Products and Solutions', 'contact-us' => 'Contact Our Packaging Team',
    );
    if ( is_singular( 'page' ) && isset( $titles[ get_post_field( 'post_name', get_queried_object_id() ) ] ) ) return $titles[ get_post_field( 'post_name', get_queried_object_id() ) ] . ' | ' . get_bloginfo( 'name' );
    if ( is_home() ) return 'Packaging News and Insights | ' . get_bloginfo( 'name' ) . ( get_query_var( 'paged' ) > 1 ? ' | Page ' . (int) get_query_var( 'paged' ) : '' );
    if ( is_post_type_archive( 'rp_job' ) ) return 'Packaging Careers and Opportunities | ' . get_bloginfo( 'name' ) . ( get_query_var( 'paged' ) > 1 ? ' | Page ' . (int) get_query_var( 'paged' ) : '' );
	return $title;
}

// Use | as the title separator instead of the default –
add_filter( 'document_title_separator', function() { return '|'; } );

}

// Rockpack-specific values are adapters; Portable SEO remains theme-independent.
if ( class_exists( 'Portable_SEO' ) ) {
    add_filter( 'portable_seo_legacy_meta', static function ( $value, $id, $key ) {
        $legacy = array( 'title'=>'_rp_seo_title', 'description'=>'_rp_seo_description', 'image'=>'_rp_seo_og_image', 'noindex'=>'_rp_seo_noindex' );
        return isset( $legacy[$key] ) ? get_post_meta( $id, $legacy[$key], true ) : $value;
    }, 10, 3 );
    add_filter( 'portable_seo_sitemap_noindex_query', static function ( $query ) {
        return array( 'relation'=>'OR',
            array( 'key'=>'_portable_seo_noindex', 'value'=>'1', 'compare'=>'!=' ),
            array( 'relation'=>'AND', array( 'key'=>'_portable_seo_noindex', 'compare'=>'NOT EXISTS' ),
                array( 'relation'=>'OR', array( 'key'=>'_rp_seo_noindex', 'compare'=>'NOT EXISTS' ), array( 'key'=>'_rp_seo_noindex', 'value'=>'1', 'compare'=>'!=' ) ) ),
        );
    } );
    add_filter( 'portable_seo_default_home_title', static function ( $default ) { return get_bloginfo('name') . ' | ' . rp_option( 'seo_home_title_suffix', 'Aseptic Packaging Manufacturer – Egypt, MENA & Africa' ); } );
    add_filter( 'portable_seo_default_description', static function ( $default ) { return rp_option( 'seo_default_description', '' ) ?: $default ?: 'The Rock Pack provides aseptic packaging solutions, combining manufacturing expertise, quality and innovation for brands across Egypt, the Middle East and Africa.'; } );
    add_filter( 'portable_seo_default_image', static function ( $default ) { return rp_option( 'seo_og_image', '' ) ?: get_template_directory_uri() . '/assets/seo/social-default.jpg'; } );
    add_filter( 'portable_seo_default_organization_logo', static function ( $default ) { return rp_option( 'nav_brand_image', get_template_directory_uri() . '/assets/images/certifications/logo.png' ); } );
    add_filter( 'portable_seo_default_socials', static function ( $default ) { $urls=array(); foreach ( rockpack_collection('footer_social') as $row ) if ( !empty($row['url']) && in_array(wp_parse_url($row['url'], PHP_URL_SCHEME), array('http','https'), true) ) $urls[]=esc_url_raw($row['url']); return implode("\n", $urls); } );
    add_filter( 'portable_seo_organization_schema', static function ( $schema ) {
        $settings=get_option(Portable_SEO::OPTION,array()); if (!is_array($settings)) $settings=array();
        $phone=rp_option('contact_phone',''); $email=rp_option('contact_email',''); $address=rp_option('contact_address','');
        if ($phone && !array_key_exists('organization_phone',$settings)) $schema['telephone']=$phone;
        if ($email && !array_key_exists('organization_email',$settings)) $schema['email']=$email;
        if ($address && !array_key_exists('organization_address',$settings)) $schema['address']=array('@type'=>'PostalAddress','streetAddress'=>$address);
        return $schema;
    } );
    add_filter( 'portable_seo_section_description', static function ( $description, $id ) {
        if ( is_front_page() ) return rp_option('seo_home_description','');
        if ( is_home() ) return 'Read the latest news and insights from The Rock Pack on aseptic packaging, manufacturing, production quality and responsible sourcing across our markets.';
        if ( is_post_type_archive('rp_job') ) return 'Explore career opportunities at The Rock Pack and learn how our people contribute to quality, innovation and growth in aseptic packaging manufacturing.';
        $defaults=array(
            'about-us'=>'Discover The Rock Pack, our experience in packaging and material science, and how our manufacturing expertise supports quality and lasting partnerships.',
            'history'=>'Explore The Rock Pack’s history and the milestones behind our experience in packaging, manufacturing, printing and material science across generations.',
            'certifications'=>'Learn about The Rock Pack’s quality systems, certifications and commitment to safe, consistent aseptic packaging production and international standards.',
            'planet'=>'Discover The Rock Pack’s approach to responsible sourcing, environmental considerations and long-term sustainability in our packaging solutions.',
            'product-portfolio'=>'Explore The Rock Pack’s aseptic packaging products, materials and manufacturing capabilities, supporting brands and their needs across diverse markets.',
            'contact-us'=>'Contact The Rock Pack to discuss aseptic packaging and manufacturing capabilities, and find out how our experienced team can support your business.',
        );
        return $id && is_page() ? ($defaults[get_post_field('post_name',$id)] ?? $description) : $description;
    }, 10, 2 );
    add_filter( 'portable_seo_document_title', static function ( $title ) {
        $titles=array('about-us'=>'About Our Packaging Expertise','history'=>'Our Packaging Manufacturing History','certifications'=>'Packaging Quality and Certifications','planet'=>'Responsible Packaging and Our Planet','product-portfolio'=>'Aseptic Packaging Products and Solutions','contact-us'=>'Contact Our Packaging Team');
        if (is_singular('page') && isset($titles[get_post_field('post_name',get_queried_object_id())])) return $titles[get_post_field('post_name',get_queried_object_id())].' | '.get_bloginfo('name');
        if (is_home()) return 'Packaging News and Insights | '.get_bloginfo('name').(get_query_var('paged')>1?' | Page '.(int)get_query_var('paged'):'');
        if (is_post_type_archive('rp_job')) return 'Packaging Careers and Opportunities | '.get_bloginfo('name').(get_query_var('paged')>1?' | Page '.(int)get_query_var('paged'):'');
        return $title;
    } );
    add_filter( 'portable_seo_canonical_url', static function ( $url, $page ) {
        if (is_home()) return $page>1 ? add_query_arg('page',$page,rockpack_news_url()) : rockpack_news_url();
        if (is_post_type_archive('rp_job')) return $page>1 ? trailingslashit(rockpack_careers_url()).user_trailingslashit('page/'.$page,'paged') : rockpack_careers_url();
        return $url;
    }, 10, 2 );
    add_action( 'portable_seo_after_canonical', static function () {
        if ( !is_home() && !is_post_type_archive('rp_job') ) return;
        global $wp_query; $page=max(1,(int)get_query_var('paged')); $jobs=is_post_type_archive('rp_job'); $base=$jobs?rockpack_careers_url():rockpack_news_url();
        $page_url=static function($n) use($base,$jobs) { return 1===$n?$base:($jobs?trailingslashit($base).user_trailingslashit('page/'.$n,'paged'):add_query_arg('page',$n,$base)); };
        if ($page>1) echo '<link rel="prev" href="'.esc_url($page_url($page-1)).'">'."\n";
        if ($page<(int)$wp_query->max_num_pages) echo '<link rel="next" href="'.esc_url($page_url($page+1)).'">'."\n";
    } );
    function rp_seo_canonical_url() { return Portable_SEO::canonical(); }
}

// ── Static site URL rewriting ─────────────────────────────────────────────────

/**
 * Replace the local WordPress origin with the production domain in every
 * HTML file before the SSG plugin writes it to disk.
 *
 * This runs only during static generation (when the SSG plugin is active and
 * has a production domain configured). It has no effect on live WP requests.
 */
add_filter( 'rockpack_static_ssg_html', 'rp_seo_rewrite_static_urls', 10, 2 );
function rp_seo_rewrite_static_urls( $html, $post ) {
	$production = get_option( 'rockpack_static_ssg_production_domain', '' );
	if ( ! $production ) {
		return $html;
	}
	$local = rtrim( home_url(), '/' );
	if ( $local === rtrim( $production, '/' ) ) {
		return $html; // Already on production — nothing to replace.
	}
	$public = rtrim( $production, '/' );
	// WordPress also embeds the origin in oEmbed query strings and JSON URLs.
	return str_replace(
		array( $local, rawurlencode( $local ), str_replace( '/', '\\/', $local ) ),
		array( $public, rawurlencode( $public ), str_replace( '/', '\\/', $public ) ),
		$html
	);
}

// Hints for the first visible home slide and font origin.
add_action( 'wp_head', static function () {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    if ( ! is_front_page() ) return;
    foreach ( rockpack_collection( 'hero_images' ) as $slide ) {
        if ( ! empty( $slide['image'] ) ) {
            echo '<link rel="preload" as="image" href="' . esc_url( rp_optimized_hero_url( $slide['image'] ) ) . '" fetchpriority="high">' . "\n";
            break;
        }
    }
}, 1 );

/** Intrinsic dimensions for local images only. Never fetch remote URLs. */
function rp_image_dimensions( $url ) {
    static $cache = array();
    if ( ! is_string( $url ) || '' === $url ) return '';
    if ( isset( $cache[ $url ] ) ) return $cache[ $url ];
    $cache[ $url ] = '';
    $uploads = wp_get_upload_dir();
    $roots = array( get_template_directory_uri() => get_template_directory(), $uploads['baseurl'] => $uploads['basedir'] );
    $url_path = strtok( $url, '?#' );
    foreach ( $roots as $base_url => $base_dir ) {
        $prefix = trailingslashit( $base_url );
        if ( 0 !== strpos( $url_path, $prefix ) ) continue;
        $root = realpath( $base_dir );
        $file = realpath( $base_dir . '/' . rawurldecode( substr( $url_path, strlen( $prefix ) ) ) );
        if ( ! $root || ! $file || 0 !== strpos( $file, $root . DIRECTORY_SEPARATOR ) || ! is_file( $file ) ) break;
        $size = wp_getimagesize( $file );
        if ( $size && ! empty( $size[0] ) && ! empty( $size[1] ) ) $cache[ $url ] = ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"';
        break;
    }
    return $cache[ $url ];
}

/** Emit structured breadcrumbs directly beside the matching visible navigation. */
function rp_seo_breadcrumb_schema( $parent, $parent_url, $title ) {
    $current = rp_seo_canonical_url();
    if ( is_search() || is_404() || ! $parent || ! $parent_url || ! $title || ! $current || untrailingslashit( $parent_url ) === untrailingslashit( $current ) || ( class_exists('Portable_SEO') && !Portable_SEO::enabled() ) ) return;
    $schema = array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array(
        array( '@type' => 'ListItem', 'position' => 1, 'name' => wp_strip_all_tags( $parent ), 'item' => esc_url_raw( $parent_url ) ),
        array( '@type' => 'ListItem', 'position' => 2, 'name' => wp_strip_all_tags( $title ), 'item' => esc_url_raw( $current ) ),
    ) );
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n";
}

// Respect per-page noindex in WordPress's own sitemap as well as the custom sitemap.
if ( ! class_exists( 'Portable_SEO' ) ) {
add_filter( 'wp_sitemaps_posts_query_args', static function ( $args ) {
    $args['meta_query'][] = array( 'relation' => 'OR', array( 'key' => '_rp_seo_noindex', 'compare' => 'NOT EXISTS' ), array( 'key' => '_rp_seo_noindex', 'value' => '1', 'compare' => '!=' ) );
    return $args;
} );
add_action( 'send_headers', static function () {
    if ( in_array( wp_get_environment_type(), array( 'local', 'development', 'staging' ), true ) ) header( 'X-Robots-Tag: noindex, nofollow', false );
} );
add_filter( 'rest_post_dispatch', static function ( $response ) { $response->header( 'X-Robots-Tag', 'noindex' ); return $response; } );

}

// Browser icons and manifest are bundled with the theme for static and dynamic delivery.
add_action( 'after_setup_theme', static function () { remove_action( 'wp_head', 'wp_site_icon', 99 ); } );
add_action( 'wp_head', static function () {
    $base = get_template_directory_uri() . '/assets/seo/';
    echo '<link rel="icon" href="' . esc_url( $base . 'favicon.ico' ) . '" sizes="any">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url( $base . 'apple-touch-icon.png' ) . '">' . "\n";
    echo '<link rel="manifest" href="' . esc_url( $base . 'site.webmanifest' ) . '">' . "\n";
    echo '<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">' . "\n";
    echo '<meta name="theme-color" content="#0a4438" media="(prefers-color-scheme: dark)">' . "\n";
}, 4 );

/** Keep the homepage preload and image on the same optimized URL. */
function rp_optimized_hero_url( $url ) {
    return rp_webp_image_url( $url );
}

/** Prefer a pre-generated smaller WebP sibling for local display images only. */
function rp_webp_image_url( $url ) {
    static $cache = array();
    if ( ! is_string( $url ) || ! preg_match( '~\.(?:jpe?g|png)(?:[?#]|$)~i', $url ) ) return $url;
    if ( isset( $cache[$url] ) ) return $cache[$url];
    $cache[$url] = $url;
    $parts = wp_parse_url( $url );
    if ( ! $parts || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) return $url;
    $uploads = wp_get_upload_dir();
    foreach ( array( get_template_directory_uri() . '/assets/images' => get_template_directory() . '/assets/images', $uploads['baseurl'] => $uploads['basedir'] ) as $base_url => $base_dir ) {
        $prefix = trailingslashit( $base_url );
        if ( 0 !== strpos( $url, $prefix ) ) continue;
        $path_url = strtok( $url, '?#' );
        $root = realpath( $base_dir );
        $source = realpath( $base_dir . '/' . rawurldecode( substr( $path_url, strlen( $prefix ) ) ) );
        if ( ! $root || ! $source || 0 !== strpos( $source, $root . DIRECTORY_SEPARATOR ) || ! is_file( $source ) ) break;
        $webp = realpath( $source . '.webp' );
        if ( ! $webp || 0 !== strpos( $webp, $root . DIRECTORY_SEPARATOR ) || ! is_file( $webp ) || filemtime( $webp ) < filemtime( $source ) || filesize( $webp ) >= filesize( $source ) ) break;
        $cache[$url] = $path_url . '.webp' . substr( $url, strlen( $path_url ) );
        break;
    }
    return $cache[$url];
}

// Keep Media Library metadata and social image URLs unchanged; only rewrite display markup.
add_filter( 'wp_get_attachment_image_attributes', static function ( $attributes ) {
    if ( ! is_admin() && ! empty( $attributes['src'] ) ) $attributes['src'] = rp_webp_image_url( $attributes['src'] );
    return $attributes;
} );
add_filter( 'wp_calculate_image_srcset', static function ( $sources ) {
    if ( is_admin() || ! is_array( $sources ) ) return $sources;
    foreach ( $sources as &$source ) if ( ! empty( $source['url'] ) ) $source['url'] = rp_webp_image_url( $source['url'] );
    unset( $source );
    return $sources;
} );
