# Database Seeding Guide
This guide contains all necessary terminal commands to seed the database with sample data and import records.
 **IMPORTANT**: Run these commands in the exact order listed below.

 
php artisan tmdb:import-genres
php artisan tmdb:import-movies
php artisan db:seed --class=TheaterSeeder
php artisan db:seed --class=RoomSeeder
php artisan db:seed --class=SeatTypeSeeder
php artisan db:seed --class=SeatSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=StaffSeeder
php artisan db:seed --class=WebUserSeeder
php artisan db:seed --class=FoodSeeder
php artisan db:seed --class=ShowtimeSeeder
php artisan db:seed --class=DayModifierSeeder
php artisan db:seed --class=TimeSlotModifierSeeder
php artisan db:seed --class=BookingSeeder

