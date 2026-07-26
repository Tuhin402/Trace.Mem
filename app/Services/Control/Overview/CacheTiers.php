<?php

namespace App\Services\Control\Overview;

enum CacheTiers: int
{
    /**
     * Tier A (Real-Time)
     * Never cached (or 15 seconds max if heavily hit)
     * Platform Health, Failed Jobs, Queue Status, Active Alerts
     */
    case REAL_TIME = 0; 
    
    /**
     * Tier B (Near Real-Time)
     * Cached for 60 seconds
     * Recent Users, Recent Tenants, Recent Workspaces, Notifications, Activity Feed
     */
    case NEAR_REAL_TIME = 60;

    /**
     * Tier C (Aggregated Metrics)
     * Cached for 300 seconds (5 minutes)
     * Total Users, Total Workspaces, Memories, Revenue, Memory Growth, API Trends
     */
    case AGGREGATED = 300;

    /**
     * Tier D (Heavy Analytics)
     * Cached for 900 seconds (15 minutes)
     * Long-term charts, Weekly trends, Forecasts
     */
    case HEAVY = 900;
}
