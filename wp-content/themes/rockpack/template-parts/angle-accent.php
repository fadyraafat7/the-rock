<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( isset( $args['variant'] ) && 'about' === $args['variant'] ) : ?>
<svg class="home-angle-accent home-angle-accent--about" viewBox="0 0 159 413" preserveAspectRatio="<?php echo ! empty( $args['align_right'] ) ? 'xMaxYMid meet' : 'xMidYMid meet'; ?>" fill="none" aria-hidden="true"><path d="M166.998 19.9961L31.7299 192.685C21.1448 206.2 23.5151 225.746 37.0303 236.331L166.998 338.142V19.9961Z" fill="#0a4438"/><path d="M169.329 41.6094L34.6287 230.883C24.6731 244.872 27.945 264.286 41.9342 274.242L169.329 364.908V41.6094Z" stroke="#3eb978" stroke-width="4" stroke-miterlimit="10"/></svg>
<?php else : ?>
<svg class="home-angle-accent" viewBox="0 0 190 430" preserveAspectRatio="<?php echo ! empty( $args['align_right'] ) ? 'xMaxYMid meet' : 'xMidYMid meet'; ?>" fill="none" aria-hidden="true"><path d="M190 0 20 238Q2 260 20 280L190 410Z" fill="#0a4438"/><path d="M190 22 18 267Q2 290 22 307L190 430" stroke="#3eb978" stroke-width="3"/></svg>
<?php endif; ?>
