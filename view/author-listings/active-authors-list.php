<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); global $authordata; ?><div class="active-authors">
	<h3>Active Authors</h3>
	<div>
		<ul>
			<?php foreach ($authors AS $author) : ?>
				<li>
					<h3 class="author vcard">
						<a 
							class="url fn n" 
							href="<?php echo get_author_posts_url( $author->ID, $author->user_nicename ); ?>" 
							title="<?php echo sprintf( __( 'View all posts by %s' ), $author->display_name ); ?>"
							><?php echo $author->display_name; ?></a>
					</h3><!-- .author .vcard -->
					<?php $author->user_thumbnail( '<p>', '</p>' ); ?>
					<p><?php echo $author->description; ?></p>
					<hr />
					<h2><a href="<?php $author->latest_post_permalink(); ?>"><?php $author->latest_post_title(); ?></a></h2>
					<p class="meta">Posted on <?php $author->latest_post_date( 'j F Y' ); ?> at <?php $author->latest_post_time(); ?>
					<h3>Excerpt</h3>
					<?php $author->latest_post_simple_excerpt( '[...]', 500, '' ); ?>
					<hr />
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
