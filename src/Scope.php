<?php

namespace LinkedIn;

enum Scope: string
{
    case READ_BASIC_PROFILE = 'r_basicprofile';
    case READ_LITE_PROFILE = 'r_liteprofile';
    case READ_FULL_PROFILE = 'r_fullprofile';
    case READ_EMAIL_ADDRESS = 'r_emailaddress';
    case COMPLIANCE = 'w_compliance';
    case MANAGE_COMPANY = 'rw_organization_admin';
    case SHARE_AS_ORGANIZATION = 'w_organization_social';
    case READ_ORGANIZATION_SHARES = 'r_organization_social';
    case SHARE_AS_USER = 'w_member_social';
    case READ_USER_CONTENT = 'r_member_social';
    case ADS_MANAGEMENT = 'rw_ads';
    case READ_ADS = 'r_ads';
    case READ_LEADS = 'r_ads_leadgen_automation';
    case READ_ADS_REPORTING = 'r_ads_reporting';
    case READ_WRITE_DMP_SEGMENTS = 'rw_dmp_segments';
}
