<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><div class="active-authors">
	<h3>Inactive Authors</h3>
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
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
