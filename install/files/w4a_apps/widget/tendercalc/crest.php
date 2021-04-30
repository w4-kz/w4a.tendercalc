<?
	require_once ('settings.php');
	/**
	 *  define:
	 *      C_REST_WEB_HOOK_URL = 'https://rest-api.bitrix24.com/rest/1/doutwqkjxgc3mgc1/'  //url on creat Webhook
	 *      or
	 *      C_REST_CLIENT_ID = 'local.5c8bb1b0891cf2.87252039' //Application ID
	 *      C_REST_CLIENT_SECRET = 'SakeVG5mbRdcQet45UUrt6q72AMTo7fkwXSO7Y5LYFYNCRsA6f'//Application key
	 *
	 *
	 *      C_REST_BLOCK_LOG = true //turn off default logs
	 *      C_REST_LOGS_DIR = __DIR__ .'/logs/' //directory path to save the log
	 *      C_REST_LOG_TYPE_DUMP = true //logs save var_export for viewing convenience
	 *      C_REST_IGNORE_SSL = true //turn off validate ssl by curl
	 */
	
	class CRest
	{
		const BATCH_COUNT    = 50;//count batch 1 query
		const TYPE_TRANSPORT = 'json';// json or xml
		
		/**
		 * call where install application even url
		 * only for rest application, not webhook
		 */

		public static function installApp()
		{
			$result = [
				'rest_only' => true,
				'install' => false
			];
			if($_REQUEST[ 'event' ] == 'ONAPPINSTALL' && !empty($_REQUEST[ 'auth' ]))
			{
				$result['install'] = static ::setAppSettings($_REQUEST[ 'auth' ], true);
			}
			elseif($_REQUEST['PLACEMENT'] == 'DEFAULT')
			{
				$result['rest_only'] = false;
				$result['install'] = static::setAppSettings(
					[
						'access_token' => htmlspecialchars($_REQUEST['AUTH_ID']),
						'expires_in' => htmlspecialchars($_REQUEST['AUTH_EXPIRES']),
						'application_token' => htmlspecialchars($_REQUEST['APP_SID']),
						'refresh_token' => htmlspecialchars($_REQUEST['REFRESH_ID']),
						'domain' => htmlspecialchars($_REQUEST['DOMAIN']),
						'client_endpoint' => 'https://' . htmlspecialchars($_REQUEST['DOMAIN']) . '/rest/',
					],
					true
				);
			}

			static ::setLog(
				[
					'request' => $_REQUEST,
					'result' => $result
				],
				'installApp'
			);
			return $result;
		}
		
		/**
		 * @var $arParams array
		 * $arParams = [
		 *      'method'    => 'some rest method',
		 *      'params'    => []//array params of method
		 * ];
		 * @return mixed array|string|boolean curl-return or error
		 *
		 */
		protected static function callCurl($arParams)
		{
			if(!function_exists('curl_init'))
			{
				return [
					'error'             => 'error_php_lib_curl',
					'error_information' => 'need install curl lib'
				];
			}
			$arSettings = static ::getAppSettings();
			if($arSettings !== false)
			{
				if($arParams[ 'this_auth' ] == 'Y')
				{
					$url = 'https://oauth.bitrix.info/oauth/token/';
				}
				else
				{
					$url = $arSettings[ "client_endpoint" ] . $arParams[ 'method' ] . '.' . static::TYPE_TRANSPORT;
					if(empty($arSettings[ 'is_web_hook' ]) || $arSettings[ 'is_web_hook' ] != 'Y')
					{
						$arParams[ 'params' ][ 'auth' ] = $arSettings[ 'access_token' ];
					}
				}
				$sPostFields = http_build_query($arParams[ 'params' ]);

				try
				{
					$obCurl = curl_init();
					curl_setopt($obCurl, CURLOPT_URL, $url);
					curl_setopt($obCurl, CURLOPT_RETURNTRANSFER, true);
					if($sPostFields)
					{
						curl_setopt($obCurl, CURLOPT_POST, true);
						curl_setopt($obCurl, CURLOPT_POSTFIELDS, $sPostFields);
					}
					curl_setopt(
						$obCurl, CURLOPT_FOLLOWLOCATION, (isset($arParams[ 'followlocation' ]))
						? $arParams[ 'followlocation' ] : 1
					);
					if(defined("C_REST_IGNORE_SSL") && C_REST_IGNORE_SSL === true)
					{
						curl_setopt($obCurl, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($obCurl, CURLOPT_SSL_VERIFYHOST, false);
					}
					$out = curl_exec($obCurl);
					$info = curl_getinfo($obCurl);
					if(curl_errno($obCurl))
					{
						$info[ 'curl_error' ] = curl_error($obCurl);
					}
					
					if($arParams[ 'this_auth' ] != 'Y' && static::TYPE_TRANSPORT == 'xml')//auth only json support
					{
						$result = $out;
					}
					else
					{
						$result = json_decode($out, true);
					}
					curl_close($obCurl);
					
					if(!empty($result[ 'error' ]))
					{
						if($result[ 'error' ] == 'expired_token' && empty($arParams[ 'this_auth' ]))
						{
							$result = static ::GetNewAuth($arParams);
						}
						else
						{
							$arErrorInform = [
								'expired_token'          => 'expired token, cant get new auth? Check access oauth server.',
								'invalid_token'          => 'invalid token, need reinstall application',
								'invalid_grant'          => 'invalid grant, check out define C_REST_CLIENT_SECRET or C_REST_CLIENT_ID',
								'invalid_client'         => 'invalid client, check out define C_REST_CLIENT_SECRET or C_REST_CLIENT_ID',
								'QUERY_LIMIT_EXCEEDED'   => 'Too many requests, maximum 2 query by second',
								'ERROR_METHOD_NOT_FOUND' => 'Method not found! You can see the permissions of the application: CRest::call(\'scope\')',
								'NO_AUTH_FOUND'          => 'Some setup error b24, check in table "b_module_to_module" event "OnRestCheckAuth"',
							];
							if(!empty($arErrorInform[ $result[ 'error' ] ]))
							{
								$result[ 'error_information' ] = $arErrorInform[ $result[ 'error' ] ];
							}
						}
					}
					if(!empty($info[ 'curl_error' ]))
					{
						$result[ 'error' ] = 'curl_error';
						$result[ 'error_information' ] = $info[ 'curl_error' ];
					}

					static ::setLog(
						[
							'url'    => $url,
							'info'   => $info,
							'params' => $arParams,
							'result' => $result
						],
						'callCurl'
					);
					
					return $result;
				}
				catch(Exception $e)
				{
					return [
						'error'             => 'exception',
						'error_information' => $e -> getMessage(),
					];
				}
			}
			return [
				'error'             => 'no_install_app',
				'error_information' => 'error install app, pls install local application '
			];
		}
		
		/**
		 * Generate a request for callCurl()
		 *
		 * @var $method string
		 * @var $params array method params
		 * @return mixed array|string|boolean curl-return or error
		 */

		public static function call($method, $params = [])
		{
			$arPost = [
				'method' => $method,
				'params' => $params
			];
			$result = static ::callCurl($arPost);
			return $result;
		}
		
		/**
		 * example $arData:
		 * $arData = [
		 *      'find_contact' => [
		 *          'method' => 'crm.duplicate.findbycomm',
		 *          'params' => [ "entity_type" => "CONTACT",  "type" => "EMAIL", "values" => array("info@bitrix24.com") ]
		 *      ],
		 *      'get_contact' => [
		 *          'method' => 'crm.contact.get',
		 *          'params' => [ "id" => '$result[find_contact][CONTACT][0]' ]
		 *      ],
		 *      'get_company' => [
		 *          'method' => 'crm.company.get',
		 *          'params' => [ "id" => '$result[get_contact][COMPANY_ID]', "select" => ["*"],]
		 *      ]
		 * ];
		 *
		 * @var $arData array
		 * @var $halt   integer 0 or 1 stop batch on error
		 * @return array
		 *
		 */

		public static function callBatch($arData, $halt = 0)
		{
			$arResult = [];
			if(is_array($arData))
			{
				$arDataRest = [];
				$i = 0;
				foreach($arData as $key => $data)
				{
					if(!empty($data[ 'method' ]))
					{
						$i++;
						if(static::BATCH_COUNT > $i)
						{
							$arDataRest[ 'cmd' ][ $key ] = $data[ 'method' ];
							if(!empty($data[ 'params' ]))
							{
								$arDataRest[ 'cmd' ][ $key ] .= '?' . http_build_query($data[ 'params' ]);
							}
						}
					}
				}
				if(!empty($arDataRest))
				{
					$arDataRest[ 'halt' ] = $halt;
					$arResult = static ::call('batch', $arDataRest);
				}
			}
			return $arResult;
		}
		
		/**
		 * Getting a new authorization and sending a request for the 2nd time
		 *
		 * @var $arParams array request when authorization error returned
		 * @return array query result from $arParams
		 *
		 */

		private static function GetNewAuth($arParams)
		{
			$result = [];
			$arSettings = static ::getAppSettings();
			if($arSettings !== false)
			{
				$arParamsAuth = [
					'this_auth' => 'Y',
					'params'    =>
						[
							'client_id'     => $arSettings[ 'C_REST_CLIENT_ID' ],
							'grant_type'    => 'refresh_token',
							'client_secret' => $arSettings[ 'C_REST_CLIENT_SECRET' ],
							'refresh_token' => $arSettings[ "refresh_token" ],
						]
				];
				$newData = static ::callCurl($arParamsAuth);
				if(isset($newData[ 'C_REST_CLIENT_ID' ]))
				{
					unset($newData[ 'C_REST_CLIENT_ID' ]);
				}
				if(isset($newData[ 'C_REST_CLIENT_SECRET' ]))
				{
					unset($newData[ 'C_REST_CLIENT_SECRET' ]);
				}
				if(isset($newData[ 'error' ]))
				{
					unset($newData[ 'error' ]);
				}
				if(static ::setAppSettings($newData))
				{
					$arParams[ 'this_auth' ] = 'N';
					$result = static ::callCurl($arParams);
				}
			}
			return $result;
		}
		
		/**
		 * @var $arSettings array settings application
		 * @var $isInstall  boolean true if install app by installApp()
		 * @return boolean
		 */

		private static function setAppSettings($arSettings, $isInstall = false)
		{
			$return = false;
			if(is_array($arSettings))
			{
				$oldData = static ::getAppSettings();
				if($isInstall != true && !empty($oldData) && is_array($oldData))
				{
					$arSettings = array_merge($oldData, $arSettings);
				}
				$return = static ::setSettingData($arSettings);
			}
			return $return;
		}
		
		/**
		 * @return mixed setting application for query
		*/

		private static function getAppSettings()
		{
			if(defined("C_REST_WEB_HOOK_URL") && !empty(C_REST_WEB_HOOK_URL))
			{
				$arData = [
					'client_endpoint' => C_REST_WEB_HOOK_URL,
					'is_web_hook'     => 'Y'
				];
				$isCurrData = true;
			}
			else
			{
				$arData = static ::getSettingData();
				$isCurrData = false;
				if(
					!empty($arData[ 'access_token' ]) &&
					!empty($arData[ 'domain' ]) &&
					!empty($arData[ 'refresh_token' ]) &&
					!empty($arData[ 'application_token' ]) &&
					!empty($arData[ 'client_endpoint' ])
				)
				{
					$isCurrData = true;
				}
			}
			return ($isCurrData) ? $arData : false;
		}
		
		/**
		 * Can overridden this method to change the data storage location.
		 *
		 * @return array setting for getAppSettings()
		 */

		protected static function getSettingData()
		{
			$return = json_decode(file_get_contents(__DIR__ . '/settings.json'), true);
			if(defined("C_REST_CLIENT_ID") && !empty(C_REST_CLIENT_ID))
			{
				$return['C_REST_CLIENT_ID'] = C_REST_CLIENT_ID;
			}
			if(defined("C_REST_CLIENT_SECRET") && !empty(C_REST_CLIENT_SECRET))
			{
				$return['C_REST_CLIENT_SECRET'] = C_REST_CLIENT_SECRET;
			}
			return $return;
		}
		
		/**
		 * Can overridden this method to change the data storage location.
		 *
		 * @var $arSettings array settings application
		 * @return boolean is successes save data for setSettingData()
		 */

		protected static function setSettingData($arSettings)
		{
			return  (boolean)file_put_contents(__DIR__ . '/settings.json', json_encode($arSettings));
		}
		
		/**
		 * Can overridden this method to change the log data storage location.
		 *
		 * @var $arData array of logs data
		 * @var $type   string to more identification log data
		 * @return boolean is successes save log data
		 */

		public static function setLog($arData, $type = '')
		{
			$return = false;
			if(!defined("C_REST_BLOCK_LOG") || C_REST_BLOCK_LOG !== true)
			{
				if(defined("C_REST_LOGS_DIR"))
				{
					$path = C_REST_LOGS_DIR;
				}
				else
				{
					$path = __DIR__ . '/logs/';
				}
				$path .= date("Y-m-d/H") . '/';
				@mkdir($path, 0775, true);
				$path .= time() . '_' . $type . '_' . rand(1, 9999999) . 'log';
				if(!defined("C_REST_LOG_TYPE_DUMP") || C_REST_LOG_TYPE_DUMP !== true)
				{
					$return = file_put_contents($path . '.json', json_encode($arData));
				}
				else
				{
					$return = file_put_contents($path . '.txt', var_export($arData, true));
				}
			}
			return $return;
		}

		/**
		 * check minimal settings server to work CRest
		 * @var $print boolean
		 * @return array of errors
		 */
		public static function checkServer($print = true)
		{
			$return = [];

			//check curl lib install
			if(!function_exists('curl_init'))
			{
				$return['curl_error'] = 'need install curl lib';
			}

			//creat setting file
			file_put_contents(__DIR__ . '/settings_check.json', json_encode(['test'=>'data']));
			if(!file_exists(__DIR__ . '/settings_check.json'))
			{
				$return['setting_creat_error'] = 'check permission! recommended: folders: 775, files: 664';
			}
			unlink(__DIR__ . '/settings_check.json');

			//creat logs folder and files
			$path = __DIR__ . '/logs/'.date("Y-m-d/H") . '/';
			if(!mkdir($path, 0775, true) && !file_exists($path))
			{
				$return['logs_folder_creat_error'] = 'check permission! recommended: folders: 775, files: 664';
			}
			else
			{
				file_put_contents($path . 'test.txt', var_export(['test'=>'data'], true));
				if(!file_exists($path . 'test.txt'))
				{
					$return['logs_file_creat_error'] = 'check permission! recommended: folders: 775, files: 664';
				}
				unlink($path . 'test.txt');
			}

			if($print === true)
			{
				if(empty($return))
				{
					$return['success'] = 'success!';
				}
				echo '<pre>';
				print_r($return);
				echo '</pre>';

			}

			return $return;
		}
	}