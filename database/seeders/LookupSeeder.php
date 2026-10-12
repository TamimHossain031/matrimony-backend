<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use App\Models\Location;
use App\Models\Profession;
use App\Models\Religion;
use Illuminate\Database\Seeder;

/**
 * Seeds the mandatory lookup tables (blueprint §13, §19): the Bangladeshi
 * division → district → upazila taxonomy, religions, professions, education levels.
 */
class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLocations();
        $this->seedReligions();
        $this->seedProfessions();
        $this->seedEducationLevels();
    }

    private function seedLocations(): void
    {
        // division => [districts...]
        $taxonomy = [
            'Dhaka' => ['Dhaka', 'Gazipur', 'Narayanganj', 'Tangail', 'Narsingdi', 'Kishoreganj', 'Manikganj', 'Munshiganj', 'Rajbari', 'Madaripur', 'Gopalganj', 'Faridpur', 'Shariatpur'],
            'Chattogram' => ['Chattogram', 'Cox\'s Bazar', 'Cumilla', 'Feni', 'Brahmanbaria', 'Rangamati', 'Noakhali', 'Chandpur', 'Lakshmipur', 'Khagrachhari', 'Bandarban'],
            'Rajshahi' => ['Rajshahi', 'Natore', 'Naogaon', 'Chapai Nawabganj', 'Pabna', 'Bogura', 'Sirajganj', 'Joypurhat'],
            'Khulna' => ['Khulna', 'Bagerhat', 'Satkhira', 'Jessore', 'Magura', 'Jhenaidah', 'Narail', 'Kushtia', 'Chuadanga', 'Meherpur'],
            'Barishal' => ['Barishal', 'Bhola', 'Patuakhali', 'Pirojpur', 'Barguna', 'Jhalokati'],
            'Sylhet' => ['Sylhet', 'Moulvibazar', 'Habiganj', 'Sunamganj'],
            'Rangpur' => ['Rangpur', 'Dinajpur', 'Kurigram', 'Gaibandha', 'Nilphamari', 'Panchagarh', 'Thakurgaon', 'Lalmonirhat'],
            'Mymensingh' => ['Mymensingh', 'Jamalpur', 'Netrokona', 'Sherpur'],
        ];

        foreach ($taxonomy as $division => $districts) {
            $div = Location::firstOrCreate(
                ['type' => 'division', 'name_en' => $division],
                ['parent_id' => null],
            );
            foreach ($districts as $district) {
                Location::firstOrCreate(
                    ['type' => 'district', 'name_en' => $district, 'parent_id' => $div->id],
                );
            }
        }

        foreach (UpazilaData::all() as $districtName => $upazilas) {
            $district = Location::where('type', 'district')->where('name_en', $districtName)->first();
            if (! $district) {
                continue;
            }
            foreach ($upazilas as $upazila) {
                Location::firstOrCreate(
                    ['type' => 'upazila', 'name_en' => $upazila, 'parent_id' => $district->id],
                );
            }
        }
    }

    private function seedReligions(): void
    {
        $religions = [
            ['key' => 'islam', 'name_en' => 'Islam', 'name_bn' => 'ইসলাম'],
            ['key' => 'hindu', 'name_en' => 'Hinduism', 'name_bn' => 'হিন্দু'],
            ['key' => 'christian', 'name_en' => 'Christianity', 'name_bn' => 'খ্রিস্টান'],
            ['key' => 'buddhist', 'name_en' => 'Buddhism', 'name_bn' => 'বৌদ্ধ'],
            ['key' => 'other', 'name_en' => 'Other / prefer not to say', 'name_bn' => 'অন্যান্য'],
        ];
        foreach ($religions as $r) {
            Religion::firstOrCreate(['key' => $r['key']], $r);
        }
    }

    private function seedProfessions(): void
    {
        $professions = [
            'student' => 'Student',
            'doctor' => 'Doctor',
            'engineer' => 'Engineer',
            'teacher' => 'Teacher',
            'software_developer' => 'Software Developer',
            'government_service' => 'Government Service',
            'private_service' => 'Private Service',
            'business' => 'Business',
            'banker' => 'Banker',
            'lawyer' => 'Lawyer',
            'accountant' => 'Accountant',
            'nurse' => 'Nurse',
            'farmer' => 'Farmer',
            'homemaker' => 'Homemaker',
            'freelancer' => 'Freelancer',
            'other' => 'Other',
        ];
        foreach ($professions as $key => $name) {
            Profession::firstOrCreate(['key' => $key], ['name_en' => $name]);
        }
    }

    private function seedEducationLevels(): void
    {
        $levels = [
            ['key' => 'below_ssc', 'name_en' => 'Below SSC', 'rank' => 1],
            ['key' => 'ssc', 'name_en' => 'SSC', 'rank' => 2],
            ['key' => 'hsc', 'name_en' => 'HSC', 'rank' => 3],
            ['key' => 'diploma', 'name_en' => 'Diploma', 'rank' => 4],
            ['key' => 'bachelor', 'name_en' => 'Bachelor\'s', 'rank' => 5],
            ['key' => 'master', 'name_en' => 'Master\'s', 'rank' => 6],
            ['key' => 'mphil', 'name_en' => 'MPhil', 'rank' => 7],
            ['key' => 'phd', 'name_en' => 'PhD', 'rank' => 8],
        ];
        foreach ($levels as $l) {
            EducationLevel::firstOrCreate(['key' => $l['key']], $l);
        }
    }
}
