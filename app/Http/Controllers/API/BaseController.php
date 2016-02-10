<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Schema;
use DB;

class BaseController extends Controller {

    protected static function execute($model, $params)
    {
        $params = self::sanitize($params);

        // apply filters
        if (isset($params['where'])) {
            foreach ($params['where'] as $exp) {
                $model->where(function($query) use ($exp) {
                    $temp = explode("|",$exp);
                    $target = $temp[0];
                    $operand = $temp[1];
                    $values = explode(":",$temp[2]);
                    if ($operand == "LIKE") {
                        foreach ($values as $elem) {
                            $query->where($target,$operand,"%".$elem."%");                          // TODO: check if column exists
                        }
                    }
                    elseif ($operand == "=" || $operand == "IN") {
                        $query->orWhereIn($target,$values);                                         // TODO: check if column exists
                    }
                });
            }
        }

        // apply ordering (DB level)
        if (isset($params['order'])) {
            foreach ($params['order'] as $order) {
                $order = explode("|",$order);
                $model->orderByRaw("case when ".$order[0]." is null then 1 else 0 end asc");        // TODO: check if column exists
                $model->orderBy($order[0],$order[1]);                                               // TODO: check if column exists
            }
        }

        // paginate
        $model = isset($params['paginate']) ? $model->paginate($params['paginate']) : $model->paginate(PAGINATION);
        return $model;
    }

    public static function api($params) {
        throw new \Exception('API method not implemented');
    }

    private static function sanitize($params) {

        $params['order'] = isset($params['order']) && is_array($params['order']) ? $params['order'] : array();
        $params['where'] = isset($params['where']) && is_array($params['where']) ? $params['where'] : array();
        $params['paginate'] = isset($params['paginate']) && $params['paginate'] <= MAX_PAGINATION ? $params['paginate'] : PAGINATION;

        foreach ($params['order'] as $key => $order) {
            if (is_string($order)) {
                $temp = explode("|",$order);
                $temp[1] = isset($temp[1]) ? strtoupper($temp[1]) : 'ASC';
                if ((count($temp) != 2) || ($temp[1] != 'ASC' && $temp[1] != 'DESC')) {
                    unset($params['order'][$key]);
                }
                else {
                    $params['order'][$key] = implode("|",$temp);
                }
            }
            else {
                unset($params['order'][$key]);
            }
        }

        foreach ($params['where'] as $key => $where) {
            if (is_string($where)) {
                
                $temp = explode("|",$where);
                $temp[1] = (isset($temp[1])) ? strtoupper($temp[1]) : null;
                $temp[1] = (isset($temp[1]) && ($temp[1] == "LIKE" || $temp[1] == "=" || $temp[1] == "IN")) ? $temp[1] : null;
                    
                if ((count($temp) != 3) || is_null($temp[1])) {
                    unset($params['where'][$key]);
                }
                else {
                    $params['where'][$key] = implode("|",$temp);
                }
            }
            else {
                unset($params['where'][$key]);
            }
        }

        return $params;
    }
}