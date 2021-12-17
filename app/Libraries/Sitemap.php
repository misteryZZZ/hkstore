<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\DB;


	class Sitemap 
	{

		/**
		*	Add new url to a given Sitemap file
		*
		* @param array | string  $item_url
		* loc corresponds to item's url
		* @param string  $file_name, if null the $table_name will be used instead
		* @return void
		**/
		public static function append($item_url, string $file_name)
		{
			$xml  = '';
			$file = public_path("{$file_name}.xml");
			
			$item_url = is_array($item_url) ? $item_url : [$item_url];
			$item_url = implode('', $item_url);

			if(is_file($file))
      {
      	$xml = Self::prepare_sitemap($file);
      	$xml = $xml . $item_url;
      }
      else
      {
      	$xml = $item_url;
      }

      $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$xml.'</urlset>';

			return file_put_contents($file, Self::format_xml($xml));
		}



		/**
		*	Update a given Sitemap file
		*
		* @param array|string $item_new_url
		* @param string  $file_name
		* @param string $item_old_url
		* @return void
		**/
		public static function update(string $item_old_url, string $item_new_url, string $file_name)
		{
			$file = public_path("{$file_name}.xml");

			$item_new_url = is_array($item_new_url) ? $item_new_url : [$item_new_url];
      $item_old_url = is_array($item_old_url) ? $item_old_url : [$item_old_url]; 

			if(is_file($file))
      {
	    	$xml = Self::prepare_sitemap($file);

	    	$xml = str_replace($item_old_url, $item_new_url, $xml);
      }
      else
      {
        $xml = implode('', $item_new_url);
      }

      $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$xml.'</urlset>';

			return file_put_contents($file, Self::format_xml($xml));
		}



		/**
		*	Create Sitemap file
		*
		* @param string $table_name
		* @param string $file_name, if null the $table_name will be used instead
		* @return void
		**/
		public static function create(string $table_name, $file_name = null)
		{
			$file_name = $file_name ?? $table_name;

      if($sitemap_urls =  Self::get_urls($table_name))
      {
          $sitemap_urls = array_column($sitemap_urls, 'url');

          $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.implode('', $sitemap_urls).'</urlset>';

          $file = public_path("{$file_name}.xml");

					return file_put_contents($file, Self::format_xml($xml));
      }
		}




		/**
		*	Update a given Sitemap file
		*
		* @param array  $sitemap_url_data - (assoc array with 'loc' key)
		* loc corresponds to item's url
		* @param string  $file_name - if null the $table_name will be used instead
		* @param mixed array|string $item_url
		* @return void
		**/
		public static function delete($item_url, string $file_name)
		{
			$file	= public_path("{$file_name}.xml");

			if(! is_file($file)) return;

			$item_url = is_array($item_url) ? $item_url : [$item_url];

    	$xml = Self::prepare_sitemap($file);

    	$xml = Self::format_xml('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.str_replace($item_url, '', $xml).'</urlset>');

      return file_put_contents($file, $xml);
		}




		/**
		*	Generate sitemap urls from database
		* Each table must have (slug, created_at, updated_at) columns
		* Each table must have (slug, active) indexes
		*
		* @param string $table_name
		* @return array	
		**/
		public static function get_urls(string $table_name)
    {
    	$base_url = '';

    	switch($table_name)
    	{
    		case 'products':
    			$base_url = url('/item').'/';
    			return DB::select("SELECT CONCAT('<url>', CONCAT('<loc>', CONCAT(?, CONCAT(id, CONCAT('/', slug))), '</loc>'), '</url>') AS url 
														 FROM $table_name USE INDEX(slug, active) WHERE active = 1 ORDER BY id ASC", [$base_url]);
    			break;
    		case 'posts':
    			$base_url = url('/blog').'/';
    			break;
    		case 'pages':
    			$base_url = url('/page').'/';
    			break;
    		default:
    			exists_or_abort(null, 'Unsupported table name.');
    			break;
    	}

    	return DB::select("SELECT CONCAT('<url>', CONCAT('<loc>', CONCAT(?, slug), '</loc>'), '</url>') AS url 
												FROM $table_name USE INDEX(slug, active) WHERE active = 1 ORDER BY id ASC", [$base_url]);
    }



    private static function prepare_sitemap($file)
    {
    	$xml = file_get_contents($file);

    	$start = stripos($xml, '<url>');
    	$end   = stripos($xml, '</urlset>');

    	return str_ireplace(' ', '', remove_nl(mb_substr($xml, $start, $end - $start)));
    }



    private static function format_xml($xml_content)
    {
    	$dom = new \DOMDocument('1.0');

			$dom->preserveWhiteSpace = true;
			$dom->formatOutput = true;
			$dom->loadXML($xml_content);

			return $dom->saveXML();
    }
	}