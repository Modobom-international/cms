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
    const CREATE_PROJECT_CLOUDFLARE_PAGE = 'create_project_cloudflare_page';
    const UPDATE_PROJECT_CLOUDFLARE_PAGE = 'update_project_cloudflare_page';
    const CREATE_DEPLOY_CLOUDFLARE_PAGE = 'create_deploy_cloudflare_page';
    const APPLY_PAGE_DOMAIN_CLOUDFLARE_PAGE = 'apply_page_domain_cloudflare_page';
    const DEPLOY_EXPORT_CLOUDFLARE_PAGE = 'deploy_export_cloudflare_page';
    const DETAIL_MONITOR_SERVER = 'detail_monitor_server';
}
