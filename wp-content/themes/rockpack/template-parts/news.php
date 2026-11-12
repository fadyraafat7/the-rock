<?php
$assets = get_template_directory_uri() . '/assets/images/home/';
$news = rockpack_latest_news(3);
$articles = array();
if ( $news ) {
    foreach ( $news as $index => $news_post ) {
        $articles[] = array( 'title' => get_the_title( $news_post ), 'image' => get_the_post_thumbnail_url( $news_post->ID, 'thumbnail' ) ?: $assets . 'news-' . ( $index + 1 ) . '.jpg', 'date' => get_the_date( '', $news_post ), 'author' => get_the_author_meta( 'display_name', $news_post->post_author ), 'url' => get_permalink( $news_post ) );
    }
}
?>
<section class="rp-news" id="news" aria-labelledby="home-news-title">
  <h2 id="home-news-title"><?php echo esc_html( rp_option( 'news_heading', 'News' ) ); ?></h2>
  <p class="sub"><?php echo esc_html( rp_option( 'news_subtitle', 'Stay Connected With The Latest Updates From The Rock Pack.' ) ); ?></p>
  <div class="news-grid">
    <div class="news-main"><?php if ( rp_option( 'news_image', $assets . 'news-factory.jpg' ) ) : ?><img src="<?php echo esc_url(rp_webp_image_url(  rp_option( 'news_image', $assets . 'news-factory.jpg' )  )); ?>" alt="<?php echo esc_attr(rp_option('home_image_description_72','The Rock Pack production facility')); ?>" width="665" height="658" loading="lazy"><?php endif; ?></div>
    <div class="news-copy">
      <p class="news-intro"><?php echo esc_html( rp_option( 'news_intro', 'From production milestones and technological advancements to strategic partnerships and regional expansion, our news section reflects how we continue to evolve alongside the needs of the industry.' ) ); ?></p>
      <?php foreach ( $articles as $article ) : ?>
      <article class="article-card">
        <?php if ( $article['image'] ) : ?><img class="art-thumb" src="<?php echo esc_url(rp_webp_image_url(  $article['image']  )); ?>" alt="" width="112" height="94" loading="lazy"><?php endif; ?>
        <div><h3 class="art-title"><?php if ( $article['url'] ) : ?><?php if ( ($article['url']) && ($article['title']) ) : ?><a href="<?php echo esc_url( $article['url'] ); ?>"><?php echo esc_html( $article['title'] ); ?></a><?php endif; ?><?php else : echo esc_html( $article['title'] ); endif; ?></h3><p class="art-meta"><?php echo esc_html( $article['date'] ); ?><span class="dsep" aria-hidden="true"></span><span><?php echo esc_html(rp_option('news_author_prefix','by')); ?> <?php echo esc_html( $article['author'] ); ?></span></p></div>
      </article>
      <?php endforeach; ?>
      <?php if ( ! $articles ) : ?><p class="news-empty"><?php esc_html_e('News updates are coming soon. Check back for the latest from The Rock Pack.','rockpack'); ?></p><?php endif; ?>
      <a class="link-arr news-all" href="<?php echo esc_url(rockpack_news_url()); ?>"><?php esc_html_e('View all news','rockpack'); ?> <span aria-hidden="true">&rarr;</span></a>
    </div>
  </div>
</section>
