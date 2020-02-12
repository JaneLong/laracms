<?php
/**
 * LaraCMS - CMS based on laravel
 *
 * @category  LaraCMS
 * @package   Laravel
 * @author    Wanglelecc <wanglelecc@gmail.com>
 * @date      2018/06/06 09:08:00
 * @copyright Copyright 2018 LaraCMS
 * @license   https://opensource.org/licenses/MIT
 * @github    https://github.com/wanglelecc/laracms
 * @link      https://www.laracms.cn
 * @version   Release 1.0
 */

namespace Wanglelecc\Laracms\Http\Controllers;

use Wanglelecc\Laracms\Http\Requests\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * 基础控制器
 *
 * Class Controller
 * @package Wanglelecc\Laracms\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 获取表单跳转
     *
     * @param null $route
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function redirect($route = null)
    {

        if ($redirect = request('redirect')) {
            return redirect($redirect);
        }

        $args = func_get_args();
        return $route === null ? redirect(url()->previous()) : redirect()->route(...$args);
    }

    public function success($data = [])
    {

        //加@符号，防制$data不是数组的情况下，报错
        if (@array_key_exists('ErrCode', $data)){
            return $this->fail($data['ErrCode'],$data['messages']);
        }else {

            return response()->json([
                'return_code' => 'SUCCESS',
                'request' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                'message' => config('errorcode.code')[200],
                'is_error' => false,
                'data' => $data,
            ]);
        }

    }

    public function fail($code, $data='')
    {
        return response()->json([
            'return_code'    => 'SUCCESS',
            'request' =>'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
            'message' => !empty($data)?$data:config('errorcode.code')[(int) $code],
            "error_code"=>$code,
            "api_code"=>$code,

        ]);
    }


    public function failEx(\Exception $e )
    {
        $code = $e->getCode();
        $debug_error = $e->getMessage();
        $line = $e->getLine();
        $file = $e->getFile();
        $file = explode("/",$file);
        $file = end($file);
        if(isset(config('errorcode.code')[(int) $code])&&$code!=400112){
            $data = config('errorcode.code')[(int) $code];
        }else{
            $data = $e->getMessage();
        }
        if(!$code){
            $code = 400123;
        }
        $http_code = substr($code,0,3);

        if(!is_integer($http_code)){
            $http_code = 400;
        }
        if(strpos($data,'SQLSTATE')!=false){
            $data="数据错误,请联系管理员！";
        }

        return response()->json([
            'return_code'    => 'SUCCESS',
            'request' =>'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
            'message' => $data,
            'error' => $data,
            'debug_error' => $debug_error ." line:".$line." file:".$file,
            "error_code"=>$code,
            'is_error' => true,
            "data"=> null,
            "api_code"=>$code,
        ],$http_code);

    }

    public function Validator($request,$rules,$messages = [],$returnall=false){
        $validator = Validator::make($request->all(),$rules,$messages);
        $errors = $validator->errors();
        $error_str= '';
        foreach ($errors->all() as $message) {
            $error_str = $error_str.$message."；";
            if ($returnall){
                throw new \Exception($error_str, 400112);
            }
        }

        if ($error_str!=''){
            throw new \Exception($error_str, 400112);
        }
    }
}
