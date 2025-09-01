<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\permission;
use App\Models\productCategory;
use Illuminate\Database\Seeder;
use App\Models\subscriptionPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StartupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        permission::create([
            'name' => 'greenbox management',
            'access_type' => 'greenbox',
            'role_for' => 'admin',
        ]);

        permission::create([
            'name' => 'fis management',
            'access_type' => 'fis',
            'role_for' => 'admin',
        ]);

        permission::create([
            'name' => 'delete_posts',
            'access_type' => 'chats',
            'role_for' => 'admin',
        ]);

        permission::create([
            'name' => 'support',
            'access_type' => 'support',
            'role_for' => 'admin',
        ]);

        permission::create([
            'name' => 'mails',
            'access_type' => 'mails',
            'role_for' => 'admin',
        ]);

        permission::create([
            'name' => 'updates',
            'access_type' => 'updates',
            'role_for' => 'admin',
        ]);

        permission::create([
            'name' => 'user mangement',
            'access_type' => 'management',
            'role_for' => 'admin',
        ]);

        // User permissions
        permission::create([
            'name' => 'Farm Management',
            'role_for' => 'user',
        ]);

        permission::create([
            'name' => 'HiB greenpay',
            'role_for' => 'user',
        ]);

        permission::create([
            'name' => 'Verified Account',
            'role_for' => 'user',
        ]);

        permission::create([
            'name' => 'Manage Sales',
            'role_for' => 'user',
        ]);

        permission::create([
            'name' => 'Track Profits and Analytics',
            'role_for' => 'user',
        ]);

        permission::create([
            'name' => 'Accesibility and to HiB logistics',
            'role_for' => 'user',
        ]);

        // Subscription Plans
        subscriptionPlan::create([
            'plan_name' => 'seller',
            'price' => 1060,
            'description' => 'Seller monthly subscription plan',
            'billing_cycle' => 'monthly',
        ]);

        subscriptionPlan::create([
            'plan_name' => 'seller',
            'price' => 12560,
            'description' => 'Seller yearly subscription plan',
            'billing_cycle' => 'yearly',
        ]);

        subscriptionPlan::create([
            'plan_name' => 'farmer',
            'price' => 1060,
            'description' => 'Farmer monthly subscription plan',
            'billing_cycle' => 'monthly',
        ]);

        subscriptionPlan::create([
            'plan_name' => 'farmer',
            'price' => 12560,
            'description' => 'Farmer yearly subscription plan',
            'billing_cycle' => 'yearly',
        ]);


        //productCategory
        productCategory::create(
            [
                'name' => 'crop',
                'description' => 'Crops from local farms.'
            ]
         );

        productCategory::create(
            [
                'name' => 'livestock',
                'description' => 'livestock animals.'
            ]
         );





        //create default admin user
        User::create([
            'firstname' => 'Super Admin',
            'lastname' => 'Hi B Greenbox',
            'phone' => '08166013343',
            'email' => 'hifarmltd@gmail.com',
            'password' => bcrypt('HiBgreenbox@2025'),
            'role' => 'admin',
            'account_status' => 'active',
            'email_verified' => true,
            'email_verified_at' => now(),
            'access_type' => 'greenbox',
            'fis_verified' => true,
            'seller_verified' => true,
            'vendor_verified' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->permissions()->sync(permission::where('role_for', 'admin')->pluck('id')->toArray());

    }
}
