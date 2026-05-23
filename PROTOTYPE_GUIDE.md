# E-Jeep Monitoring System - Prototype Guide

## Quick Start

### 1. Start the Development Server

```bash
php artisan serve
```

The application will be available at: `http://localhost:8000`

### 2. Test Accounts

#### Admin Account
- **Username:** `admin`
- **Password:** `password`
- **Access:** Full system access including E-Jeep management, driver management, route management, schedules, and trip monitoring

#### Additional Admin
- **Username:** `admin2`
- **Password:** `password`

#### Driver Accounts
All driver accounts use the password: `password`

| Username | Name | Status |
|----------|------|--------|
| driver1 | Pedro Cruz | Has completed trip |
| driver2 | Juan Reyes | Has completed trip with deviation |
| driver3 | Carlos Garcia | Currently on active trip (at capacity) |
| driver4 | Miguel Torres | Has scheduled trip |
| driver5 | Roberto Flores | Available |

### 3. Sample Data Overview

#### E-Jeeps (5 vehicles)
- **EJ-001** (ABC1234) - 20 capacity - Active
- **EJ-002** (DEF5678) - 22 capacity - Active
- **EJ-003** (GHI9012) - 18 capacity - Active
- **EJ-004** (JKL3456) - 20 capacity - Active
- **EJ-005** (MNO7890) - 25 capacity - In Maintenance

#### Routes (3 routes with stops)
1. **Main Campus Loop (MCL-01)**
   - Main Gate → Engineering Building → Library → Student Center → Cafeteria

2. **Dormitory Express (DORM-01)**
   - North Dormitory → South Dormitory → Science Building → Admin Building

3. **Sports Complex Route (SPT-01)**
   - Main Gate → Gymnasium → Swimming Pool → Track and Field

#### Active Trips
- **Completed Trip:** Morning run on Main Campus Loop (18 passengers max)
- **In-Progress Trip:** Currently running with 22 passengers (AT CAPACITY - Alert!)
- **Scheduled Trip:** Upcoming Sports Complex route at 2:00 PM
- **Completed with Deviation:** Dormitory Express with route deviation note

#### Notifications
- Route update notification for driver1
- Capacity warning for driver3 (active trip)
- Schedule change notification for driver2 (read)
- New route assignment for driver4

## Testing Scenarios

### As Admin

1. **Enhanced Dashboard Overview**
   - Login as `admin`
   - View real-time statistics: Active E-Jeeps, Drivers on Trip, Ongoing Trips, Completed Today
   - See capacity alerts with visual warnings (driver3's trip is at capacity - 22/18 passengers)
   - Check route deviations with detailed notes (driver2's completed trip)
   - Monitor active trips in real-time with passenger counts and start times
   - Review recent completed trips with duration and max passenger data

2. **E-Jeep Management**
   - Navigate to E-Jeeps section
   - View all 5 vehicles
   - Notice EJ-005 is in maintenance
   - Create, edit, or view E-Jeep details

3. **Driver Management**
   - Navigate to Drivers section
   - View all 5 drivers
   - Check driver performance metrics
   - Create new driver or edit existing

4. **Route Management**
   - Navigate to Routes section
   - View 3 active routes with stops
   - Edit route details or add new routes
   - See stops in sequence order

5. **Schedule Management**
   - Navigate to Schedules section
   - View today's schedules
   - Create new schedules
   - Assign drivers and E-Jeeps to routes

6. **Trip Monitoring**
   - View active trips in real-time
   - Check passenger counts
   - Monitor capacity alerts
   - Review completed trips

### As Driver

1. **Driver Dashboard**
   - Login as `driver3` (has active trip)
   - View current trip in progress
   - See today's schedule
   - Check unread notifications

2. **Trip Operations**
   - View current trip details
   - See route with stops
   - Check current passenger count (22/18 - OVER CAPACITY)
   - View capacity warning

3. **Notifications**
   - Login as `driver1` or `driver4`
   - View unread notifications
   - Mark notifications as read
   - Check notification history

## Features to Explore

### Real-Time Monitoring
- Admin dashboard shows live trip status
- Capacity alerts appear when E-Jeeps reach/exceed capacity
- Route deviations are flagged for review

### Data Integrity
- Try creating duplicate vehicle numbers (should fail)
- Try assigning maintenance E-Jeep to schedule (should be filtered out)
- Validate form inputs with invalid data

### Role-Based Access
- Drivers cannot access admin features
- Admins can view all system data
- Each role has appropriate dashboard

## Database Reset

To reset the database and reseed with fresh data:

```bash
php artisan migrate:fresh --seed
```

This will:
- Drop all tables
- Run all migrations
- Seed with test data

## Development Commands

### Run Tests
```bash
php artisan test
```

### Format Code
```bash
./vendor/bin/pint
```

### View Routes
```bash
php artisan route:list
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Prototype Limitations

This is a prototype with the following limitations:

1. **Real-time Updates:** Currently requires page refresh (AJAX polling not yet implemented)
2. **GPS Tracking:** Route deviation detection is manual/placeholder
3. **Reporting:** Report generation features not yet implemented
4. **Mobile Interface:** Optimized for desktop viewing
5. **Email Notifications:** Not configured (notifications are in-app only)

## Next Steps

After exploring the prototype:

1. Review the implementation plan in `.kiro/specs/e-jeep-monitoring/tasks.md`
2. Check remaining tasks to be implemented
3. Run tests to verify functionality: `php artisan test`
4. Provide feedback on UI/UX improvements
5. Identify additional features needed

## Support

For issues or questions:
- Check the requirements document: `.kiro/specs/e-jeep-monitoring/requirements.md`
- Review the design document: `.kiro/specs/e-jeep-monitoring/design.md`
- Run tests to verify functionality
