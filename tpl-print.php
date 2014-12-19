<?php 

/*** ProductPrint v 1.1 tpl-print.php ***/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//gets all the global properties required such as the query element and woocommerce

global $wp_query, $wpdb, $woocommerce, $post;  

if ( isset( $_GET['task'] ) && $_GET['task'] == 'productprint' ) {

check_admin_referer($_REQUEST['pid']); /*** the pid was encoded in the nonce, see if they match ***/

//gets the products ID
$product = get_product($_REQUEST['pid']);

//Gets the products data 
setup_postdata($product->post);

//Converts the object to an array we can easily use.
$post = $product->post; 


if(isset($_REQUEST['variation_id']) && $_REQUEST['variation_id']>0 )
{
    $variation_id=$_REQUEST['variation_id'];
    $wp_variations = $product->get_available_variations($_REQUEST['pid']);
    foreach($wp_variations as $key1=>$value1)
    {
        if($variation_id==$value1['variation_id'])
        {
            $variation_array=$value1;
        }
    }
}

//SQL statement to inner join the comments that are approved and published to the post
$groupedSQLStatement = "JOIN $wpdb->posts ON ( $wpdb->comments.comment_post_ID = $wpdb->posts.ID ) WHERE post_status = 'publish' AND comment_approved = '1' AND $wpdb->posts.ID = $post->ID ORDER BY comment_date_gmt ASC";


//Query to get the comments to the post
$query = "SELECT $wpdb->comments.* FROM $wpdb->comments $groupedSQLStatement";


//Put the comments and comment counts to the $wp_query property
$wp_query->comments = (array) $wpdb->get_results($query); //gets all the comment data
$wp_query->comment_count = count($wp_query->comments); //gets the comment count integer

//Here are the default variables to the Plugin if there aren't any settings found in the database
	$def = array(		'featured_image' => 1, 
						'gallery' => 1, 
						'product_description' => 1, 
						'price' => 1, 
						'product_attributes' => 1, 
						'short_description' => 1, 
						'stock_level' => 1, 
						'reviews' => 1,
						'header_image' => '',
						'header_text' => '', 
						'footer_text' => '',
						'img_position' => 'right',
						'img_width' => '50%',
						'img_marginleft' => '20px',
						'img_marginright' => '0px',
						'img_margintop' => '0px',
						'img_marginbottom' => '20px',
						'show_border' => '0',
						'gallery_img_width' =>'15%',
						'gallery_border' => '0',
						'font_family' => 'Arial',
						'button_position' => '4', 
						'button_legend' => 'Print',
                        'sku' => '',
						);
				


//gets the options set in Admin page
$ops = get_option('productprint_ops'); 

//the array_merge checks if there are any missing keys (settings) from $ops, our custom options in the backend
$ops = array_merge($def, $ops);

//now we are going to output the HTML to the print page

?>



<!DOCTYPE html>

<html>

<head>

<title>Print <?php print $product->post->post_title; ?></title>

<style>

body, #container {
	font-family: <?php print $ops['font_family']?>;
	}


#thumbnails img {

<?php

//this gets the width to the Gallery.

	if(isset($ops['gallery_img_width']) && $ops['gallery_img_width'] != "")
		echo "width: " . $ops[gallery_width] . "; ";

?>
	height: "auto";

}

</style>

<link rel="stylesheet" href="<?php print plugins_url( 'css/productprint.css', __FILE__ ) ?>" />

<script src="<?php print home_url('/wp-includes/js/jquery/jquery.js'); ?>"></script>

<script>

jQuery(function($)

{
		window.print(); //standard Javascript print function

});

</script>

</head>

<body>

<div id="container" style="display:block;">

    <div id="productprint-main">

	<h1 id="productprint-title"><?php print $product->post->post_title; ?></h1>

	<div id="productprint-images">

		<?php if( $ops['featured_image'] == 1 ): ?>

			<div id="productprint-main-image">

				<?php

				//this checks that the post has a thumbnail option

				if ( has_post_thumbnail() ) 
				{
				    $w=$h=0;
				    if(isset($_REQUEST['variation_id']) && $_REQUEST['variation_id']>0 )
                    {
    				    $src=$variation_array['image_src'];
						/*** we could have variations but no images for them so before calling getImageSize, check $src is not null ***/
					if (!empty($src)) {
    					list($w, $h)=getImageSize($src);
						}
                    }
					if($w>0 && $h>0){}else
					{
					    list($src, $w, $h)  = wp_get_attachment_image_src(get_post_thumbnail_id($product->post->ID), 'large');   
					}
					
					//Gets width, margin and position properties for the featured image

					$w = ($ops['img_width']) ? $ops['img_width'] : $w;

					$h = "auto";

					$ml = ($ops['img_marginleft']) ? $ops['img_marginleft'] : $def['img_marginleft'];

					$mr = ($ops['img_marginright']) ? $ops['img_marginright'] : $def['img_marginright'];

					$mt = ($ops['img_margintop']) ? $ops['img_margintop'] : $def['img_margintop'];

					$mb = ($ops['img_marginbottom']) ? $ops['img_marginbottom'] : $def['img_marginbottom'];

					$p = ($ops['img_position']) ? $ops['img_position'] : $def['img_marginposition'];

					//outputs the featured image with its correct width, height settings as well as margin top, left, bottom and right

					$featureborder = ""; //create the var

					if($ops['show_border'] == 1) $featureborder = "class='showborder'"; //if the option for border is on give it a class

					//echos the image with the inline css styles based on the properties from the option

					$image = ("<img src='{$src}' alt='' style='width: {$w}; height: {$h}; margin-left: {$ml}; margin-right: {$mr}; margin-top: {$mt}; margin-bottom: {$mb}; float: {$p}' {$featureborder} />");

					echo ($image);

				}

				?>

			</div>

		<?php endif; ?>



			<div id="productprint-price">

				<h3><?php 
    			 if(isset($_REQUEST['variation_id']) && $_REQUEST['variation_id']>0 )
                 {
				    echo $variation_array['price_html'];
                 }
				else
				{
				    echo $product->get_price_html(); 
				}
				?></h3>

			</div>


			<div id="productprint-sku">

				<?php 
    			 if(isset($_REQUEST['variation_id']) && $_REQUEST['variation_id']>0 )
                 {
                    _e('SKU: ', 'woocommerce');
				    echo $variation_array['sku'];
                 }
				else
				{
				    _e('SKU: ', 'woocommerce');
				    echo $product->get_sku(); 
				}
				?>

			</div>

		

			<div id="productprint-stock">

				<?php

				$availability = $product->get_availability();

				if ( $availability['availability'] )

					echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</p>', $availability['availability'] );

				?>

			</div>


			<div id="productprint-short-description">

				<?php $void = woocommerce_template_single_excerpt(); ?>
			</div>

			<div id="productprint-description">

			<?php
				$heading = esc_html( apply_filters( 'woocommerce_product_description_heading', __( 'Product Description', 'woocommerce' ) ) ); 
			?>

			<h2><?php echo $heading; ?></h2>

			<?php the_content(); ?>

			</div>

			<?php $attributes = $product->get_attributes(); ?>

			<?php if(count($attributes)): ?>

				<div id="additional-info">
					<?php
						$heading = apply_filters( 'woocommerce_product_additional_information_heading', __( 'Additional Information', 'woocommerce' ) );
					?>

					<?php if ( $heading ): ?>
						<h2><?php echo $heading; ?></h2>
					<?php endif; ?>

					<?php $product->list_attributes(); ?>

				</div>

			<?php endif; ?>

			<div id="thumbnails">
				
				<?php

				$attachment_count = count( $product->get_gallery_attachment_ids() );

				if ( $attachment_count > 0 ) { /** there are gallery images **/

					//Do a custom Query to pick up the images to avoid resizing.

					//First get the ID for this post

					$postparentID = $_REQUEST['pid']; 
			
					// Create the custom query that will go into the database

					$GalleryImgQuery = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '{$postparentID}' AND meta_key = '_product_image_gallery'";

					
					$GalleryImgIDs = $wpdb->get_var($GalleryImgQuery); 

					//now we have our gallery image array, put brackets around it for our next query

					$GalleryImgIDs = "(" . $GalleryImgIDs . ")"; 

					//change the query statement

					$GalleryImgQuery = "SELECT guid FROM $wpdb->posts WHERE ID IN {$GalleryImgIDs}";
	
					//get the images

					$RetrieveGalleryImgs = $wpdb->get_results($GalleryImgQuery);
	
					//loop and make those images!
				
					if($ops['gallery_border'] == 1) $galleryborder = "class='galleryborder'"; //if the option for gallery border is on give it a class

					
					
					if(count($RetrieveGalleryImgs) > 0) { //yes we have gallery images
				
						$gw = ($ops['gallery_img_width']) ? $ops['gallery_img_width'] : $def['gallery_img_width'];

						$gh = "auto";
				
						$galleryborder = ""; //create the var
	
						if($ops['gallery_border'] == 1) $galleryborder = "class='galleryborder'"; //if the option for border is on give it a class
						?>
						<?php /*** <h2><?php _e('Gallery', 'productprint'); ?></h2> ***/ ?>
						<?php
						foreach($RetrieveGalleryImgs as $TheImg){
							$galleryimage = ("<img src='{$TheImg->guid}' alt='' style='width: {$gw}; height: {$gh};' {$galleryborder} />");

							echo ($galleryimage);

						}

					}

				}
				?>

			</div>

			<div id="productprint-reviews">
	
				<h2><?php _e('Reviews', 'woocommerce'); ?></h2>

				<?php /*** star rating and review count ***/

				if ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) :


					$count   = $product->get_rating_count();
					$average = $product->get_average_rating();

					if ( $count > 0 ) : ?>

						<div class="woocommerce-product-rating" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
							<div class="star-rating" title="<?php _e (sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $average ) ); ?>">
								<span style="width:<?php echo ( ( $average / 5 ) * 100 ); ?>%">
								<strong itemprop="ratingValue" class="rating"><?php echo esc_html( $average ); ?></strong> <?php _e( 'out of 5', 'woocommerce' ); ?>
								</span>
							</div>
						<?php printf( _n( '%s customer review', '%s customer reviews', $count, 'woocommerce' ), '<span itemprop="ratingCount" class="count">' . $count . '</span>' ); ?>
						</div>

					<?php endif; ?>
		
				<?php endif; ?>
				
				<div id="comments">

					<?php if ( have_comments() ) :  ?>

						<ol class="commentlist">

							<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
	
						</ol>

					<?php else : ?>

						<p class="productprint-noreviews"><?php _e( 'There are no reviews yet.', 'woocommerce' ); ?></p>

					<?php endif; ?>

				</div>
			</div><!-- end id="productprint-reviews" -->

	</div>

    </div><!-- end id="productprint-main" -->

</div><!-- end id="container" -->

</body>

</html>

<?php	/*** the else part of the nonce test. ***/
} 