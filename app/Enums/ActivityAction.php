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
    const CREATE_PAGE_EXPORTS = 'create_page_exports';
    const CREATE_PAGES = 'create_pages';
    const UPDATE_PAGES = 'update_pages';

    // Site Management Actions
    const CREATE_SITE = 'create_site';
    const UPDATE_SITE = 'update_site';
    const DELETE_SITE = 'delete_site';
    const ACTIVATE_SITE = 'activate_site';
    const DEACTIVATE_SITE = 'deactivate_site';
    const UPDATE_SITE_LANGUAGE = 'update_site_language';
    const UPDATE_SITE_PLATFORM = 'update_site_platform';

    // Page Management Actions
    const CREATE_PAGE = 'create_page';
    const UPDATE_PAGE = 'update_page';
    const DELETE_PAGE = 'delete_page';
    const EXPORT_PAGE = 'export_page';
    const UPDATE_TRACKING_SCRIPT = 'update_tracking_script';
    const REMOVE_TRACKING_SCRIPT = 'remove_tracking_script';
    const GET_TRACKING_SCRIPT = 'get_tracking_script';
    const CANCEL_EXPORT = 'cancel_export';

    // Attendance Management Actions
    const CHECKIN_ATTENDANCE = 'checkin_attendance';
    const CHECKOUT_ATTENDANCE = 'checkout_attendance';
    const GET_ATTENDANCE = 'get_attendance';
    const GET_ATTENDANCE_REPORT = 'get_attendance_report';
    const ADD_CUSTOM_ATTENDANCE = 'add_custom_attendance';
    const UPDATE_CUSTOM_ATTENDANCE = 'update_custom_attendance';

    // Attendance Complaint Actions
    const CREATE_ATTENDANCE_COMPLAINT = 'create_attendance_complaint';
    const UPDATE_ATTENDANCE_COMPLAINT = 'update_attendance_complaint';
    const GET_ATTENDANCE_COMPLAINTS = 'get_attendance_complaints';
    const REVIEW_ATTENDANCE_COMPLAINT = 'review_attendance_complaint';
    const RESPOND_TO_ATTENDANCE_COMPLAINT = 'respond_to_attendance_complaint';
    const GET_ATTENDANCE_COMPLAINT_STATS = 'get_attendance_complaint_stats';

    // List Board Actions
    const CREATE_LIST = 'create_list';
    const UPDATE_LIST = 'update_list';
    const DELETE_LIST = 'delete_list';
    const UPDATE_LIST_POSITIONS = 'update_list_positions';
    const GET_BOARD_LISTS = 'get_board_lists';
    const ADD_BOARD_MEMBER = 'add_board_member';
    const REMOVE_BOARD_MEMBER = 'remove_board_member';
    const GET_BOARD_MEMBERS = 'get_board_members';

    // DNS Management Actions
    const SYNC_DNS_RECORDS = 'sync_dns_records';
    const DNS_DOMAIN_SYNCED = 'dns_domain_synced';
    const DNS_SYNC_COMPLETED = 'dns_sync_completed';
    const DNS_SYNC_FAILED = 'dns_sync_failed';
    const DNS_ZONE_NOT_FOUND = 'dns_zone_not_found';
    const DNS_NO_RECORDS_FOUND = 'dns_no_records_found';
    const DNS_RECORD_SYNC_FAILED = 'dns_record_sync_failed';
    const DNS_OBSOLETE_RECORDS_REMOVED = 'dns_obsolete_records_removed';
    const DNS_SYNC_BATCH_COMPLETED = 'dns_sync_batch_completed';
    const DNS_SYNC_COMPLETED_WITH_ERRORS = 'dns_sync_completed_with_errors';
    const DNS_SYNC_ERROR = 'dns_sync_error';
}
