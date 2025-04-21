<?php

namespace App\Enums;

class ActivityAction
{
    const ACCESS_VIEW = 'access_view';
    const SHOW_RECORD = 'show_record';
    const CREATE_RECORD = 'create_record';
    const UPDATE_RECORD = 'update_record';
    const DELETE_RECORD = 'delete_record';
    const GET_PERMISSiON_BY_TEAM = 'get_permission_by_team';
    const REFRESH_LIST_DOMAIN = 'refresh_list_domain';
    const GET_LIST_PATH_BY_DOMAIN = 'get_list_path_by_domain';
}
