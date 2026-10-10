<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\Gender;
use App\Enums\Importance;
use App\Enums\InterestStatus;
use App\Enums\ManagerRelationship;
use App\Enums\PhotoModerationStatus;
use App\Enums\ProfileCreatedBy;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Enums\Visibility;
use App\Models\Conversation;
use App\Models\Education;
use App\Models\EducationLevel;
use App\Models\Interest;
use App\Models\InterestEvent;
use App\Models\Location;
use App\Models\Message;
use App\Models\Notification;
use App\Models\PartnerPreference;
use App\Models\PreferenceWeight;
use App\Models\Profession;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ProfileReligionAttribute;
use App\Models\ProfileView;
use App\Models\Religion;
use App\Models\Shortlist;
use App\Models\User;
use App\Models\Verification;
use App\Services\ProfileCompletionService;
use App\Support\Phone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BangladeshUserSeeder extends Seeder
{
    private array $religions = [];

    private array $professions = [];

    private array $educationLevels = [];

    private array $districts = [];

    public function run(): void
    {
        $this->loadLookups();

        $admin = User::firstWhere('role', UserRole::Admin) ?? User::first();

        // 1. Seed or enrich Tamim's profile (the main active dev account if present)
        $tamimUser = User::where('email', 'tamimhossain031@gmail.com')->first();
        if ($tamimUser) {
            $this->seedTamimProfile($tamimUser, $admin);
        }

        // 2. Seed 16 diverse, authentic and attractive Bangladeshi candidates (8 female, 8 male)
        $candidates = $this->getCandidateDefinitions();
        $createdProfiles = [];

        foreach ($candidates as $def) {
            $createdProfiles[] = $this->createCandidateProfile($def, $admin);
        }

        // 3. Seed realistic social interactions (interests, conversations, shortlists, views, notifications)
        $this->seedInteractions($tamimUser, $createdProfiles);
    }

    private function loadLookups(): void
    {
        $this->religions = Religion::pluck('id', 'key')->all();
        $this->professions = Profession::pluck('id', 'key')->all();
        $this->educationLevels = EducationLevel::pluck('id', 'key')->all();
        $this->districts = Location::where('type', 'district')->pluck('id', 'name_en')->all();
    }

    private function seedTamimProfile(User $user, ?User $admin): void
    {
        $user->update([
            'name' => 'Tamim Hossain',
            'gender' => Gender::Male,
            'date_of_birth' => '1998-05-15',
            'status' => AccountStatus::Active,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'terms_accepted_at' => $user->terms_accepted_at ?? now(),
            'last_active_at' => now(),
        ]);

        $profile = Profile::firstOrNew(['user_id' => $user->id]);
        $profile->fill([
            'public_id' => $profile->public_id ?? 'BD-TM'.strtoupper(Str::random(6)),
            'full_name_en' => 'Tamim Hossain',
            'full_name_bn' => 'তামিম হোসেন',
            'gender' => Gender::Male,
            'date_of_birth' => '1998-05-15',
            'height_cm' => 178,
            'marital_status' => 'never_married',
            'district_id' => $this->districts['Dhaka'] ?? null,
            'nationality' => 'Bangladeshi',
            'headline' => 'Senior Software Engineer | Tech Architect, Coffee Brewer & Explorer',
            'about' => 'I am an enthusiastic software engineer passionate about modern web technologies, thoughtful system design, and building meaningful digital solutions. I value intellectual curiosity, honesty, good humor, and a peaceful lifestyle. Outside of programming, I enjoy brewing fresh coffee, playing badminton, exploring scenic outdoor spots, and spending quality time with family. I believe a successful marriage thrives on mutual respect, deep friendship, understanding, and shared life goals. Seeking an educated, kind-hearted, and cultured life partner with a cheerful demeanor and strong ethical values.',
            'religion_id' => $this->religions['islam'] ?? null,
            'education_level_id' => $this->educationLevels['bachelor'] ?? null,
            'institution' => 'University of Dhaka',
            'subject' => 'Computer Science & Engineering',
            'graduation_year' => 2021,
            'profession_id' => $this->professions['software_developer'] ?? null,
            'job_title' => 'Senior Full Stack Software Engineer',
            'organization' => 'Tech Innovators BD',
            'income_min' => 180000,
            'income_max' => 280000,
            'work_location' => 'Gulshan 2, Dhaka',
            'father_occupation' => 'Former Senior Public Servant (Retired)',
            'mother_occupation' => 'Homemaker',
            'siblings_count' => 2,
            'family_type' => 'nuclear',
            'family_location' => 'Dhanmondi, Dhaka',
            'family_description' => 'Cultured, loving family with strong moral principles and respect for education and religion.',
            'lifestyle' => [
                'smoking' => 'never',
                'drinking' => 'never',
                'diet' => 'halal',
                'hobbies' => ['Software Development', 'Coffee Brewing', 'Badminton', 'Traveling', 'Reading'],
                'languages' => ['Bangla', 'English'],
            ],
            'has_photo' => true,
            'verification_level' => VerificationLevel::Selfie->value,
        ]);
        $profile->save();

        $profile->managers()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'relationship' => ManagerRelationship::Self_,
                'can_edit' => true,
                'can_shortlist' => true,
                'can_send_interest' => true,
                'can_chat' => true,
            ]
        );

        $profile->privacySettings()->firstOrCreate([], [
            'phone_visibility' => 'accepted_connection',
            'email_visibility' => 'accepted_connection',
            'address_visibility' => 'accepted_connection',
            'family_contact_visibility' => 'accepted_connection',
            'photo_visibility' => 'public',
        ]);

        $user->entitlement()->firstOrCreate([], [
            'plan' => 'premium',
            'daily_interest_limit' => 30,
            'daily_contact_view_limit' => 50,
            'expires_at' => now()->addMonths(6),
        ]);

        Education::updateOrCreate(
            ['profile_id' => $profile->id, 'institution' => 'Dhaka College'],
            [
                'education_level_id' => $this->educationLevels['hsc'] ?? null,
                'subject' => 'Science',
                'graduation_year' => 2017,
            ]
        );
        Education::updateOrCreate(
            ['profile_id' => $profile->id, 'institution' => 'University of Dhaka'],
            [
                'education_level_id' => $this->educationLevels['bachelor'] ?? null,
                'subject' => 'Computer Science & Engineering',
                'graduation_year' => 2021,
            ]
        );

        ProfileReligionAttribute::updateOrCreate(
            ['profile_id' => $profile->id, 'key' => 'maslak'],
            ['value' => 'Sunni (Hanafi)']
        );
        ProfileReligionAttribute::updateOrCreate(
            ['profile_id' => $profile->id, 'key' => 'prayer_habit'],
            ['value' => 'Always (5 times daily)']
        );
        ProfileReligionAttribute::updateOrCreate(
            ['profile_id' => $profile->id, 'key' => 'beard'],
            ['value' => 'Trimmed beard']
        );

        $pref = PartnerPreference::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'age_min' => 22,
                'age_max' => 28,
                'height_min_cm' => 155,
                'height_max_cm' => 170,
                'marital_statuses' => ['never_married'],
                'religion_id' => $this->religions['islam'] ?? null,
                'min_education_level_id' => $this->educationLevels['bachelor'] ?? null,
                'profession_ids' => [
                    $this->professions['doctor'] ?? null,
                    $this->professions['software_developer'] ?? null,
                    $this->professions['teacher'] ?? null,
                    $this->professions['banker'] ?? null,
                    $this->professions['private_service'] ?? null,
                ],
                'income_min' => null,
                'district_ids' => [$this->districts['Dhaka'] ?? null, $this->districts['Chattogram'] ?? null],
                'other_expectations' => 'Looking for an educated, kind-hearted, family-oriented partner with shared religious values and emotional maturity.',
            ]
        );

        $weights = [
            'religion' => Importance::DealBreaker,
            'marital_status' => Importance::DealBreaker,
            'age_range' => Importance::DealBreaker,
            'education' => Importance::NiceToHave,
            'profession' => Importance::NiceToHave,
            'district' => Importance::NiceToHave,
            'height_range' => Importance::NiceToHave,
        ];
        foreach ($weights as $key => $imp) {
            PreferenceWeight::updateOrCreate(
                ['partner_preference_id' => $pref->id, 'preference_key' => $key],
                ['importance' => $imp]
            );
        }

        $this->ensureProfilePhoto($profile, 'TH', 'Tamim Hossain', 'Software Engineer', [30, 58, 138]);

        if ($admin) {
            Verification::updateOrCreate(
                ['user_id' => $user->id, 'level' => VerificationLevel::Selfie->value],
                [
                    'status' => 'approved',
                    'reviewed_by' => $admin->id,
                    'review_note' => 'National ID and selfie verified successfully.',
                    'reviewed_at' => now()->subDays(5),
                ]
            );
        }

        app(ProfileCompletionService::class)->sync($profile);
    }

    private function createCandidateProfile(array $def, ?User $admin): Profile
    {
        $e164 = Phone::normalizeBd($def['phone']);

        $user = User::updateOrCreate(
            ['email' => $def['email']],
            [
                'name' => $def['name'],
                'phone' => $def['phone'],
                'phone_e164' => $e164,
                'phone_hash' => $e164 ? Phone::hash($e164) : null,
                'password' => Hash::make('password'),
                'role' => $def['user_role'] ?? UserRole::User,
                'status' => AccountStatus::Active,
                'date_of_birth' => $def['dob'],
                'gender' => $def['gender'],
                'profile_created_by' => $def['created_by'] ?? ProfileCreatedBy::Self_,
                'email_verified_at' => now()->subDays(rand(10, 60)),
                'phone_verified_at' => now()->subDays(rand(10, 60)),
                'terms_accepted_at' => now()->subDays(rand(10, 60)),
                'last_active_at' => now()->subHours(rand(1, 36)),
            ]
        );

        $profile = Profile::firstOrNew(['user_id' => $user->id]);
        $profile->fill([
            'public_id' => $profile->public_id ?? ('BD-'.strtoupper(Str::random(8))),
            'full_name_en' => $def['name'],
            'full_name_bn' => $def['name_bn'],
            'gender' => $def['gender'],
            'date_of_birth' => $def['dob'],
            'height_cm' => $def['height_cm'],
            'marital_status' => $def['marital_status'] ?? 'never_married',
            'district_id' => $this->districts[$def['district']] ?? null,
            'nationality' => 'Bangladeshi',
            'headline' => $def['headline'],
            'about' => $def['about'],
            'religion_id' => $this->religions[$def['religion']] ?? null,
            'education_level_id' => $this->educationLevels[$def['education_level']] ?? null,
            'institution' => $def['institution'],
            'subject' => $def['subject'],
            'graduation_year' => $def['graduation_year'],
            'profession_id' => $this->professions[$def['profession']] ?? null,
            'job_title' => $def['job_title'],
            'organization' => $def['organization'],
            'income_min' => $def['income_min'],
            'income_max' => $def['income_max'],
            'work_location' => $def['work_location'],
            'father_occupation' => $def['father_occupation'],
            'mother_occupation' => $def['mother_occupation'],
            'siblings_count' => $def['siblings_count'],
            'family_type' => $def['family_type'],
            'family_location' => $def['family_location'],
            'family_description' => $def['family_description'],
            'lifestyle' => $def['lifestyle'],
            'has_photo' => true,
            'verification_level' => $def['verification_level'] ?? VerificationLevel::Nid->value,
        ]);
        $profile->save();

        $managerRel = match ($def['created_by'] ?? ProfileCreatedBy::Self_) {
            ProfileCreatedBy::Parent => ManagerRelationship::Parent,
            ProfileCreatedBy::Sibling => ManagerRelationship::Sibling,
            default => ManagerRelationship::Self_,
        };

        $profile->managers()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'relationship' => $managerRel,
                'can_edit' => true,
                'can_shortlist' => true,
                'can_send_interest' => true,
                'can_chat' => true,
            ]
        );

        $profile->privacySettings()->firstOrCreate([], [
            'phone_visibility' => 'accepted_connection',
            'email_visibility' => 'accepted_connection',
            'address_visibility' => 'accepted_connection',
            'family_contact_visibility' => 'accepted_connection',
            'photo_visibility' => 'public',
        ]);

        $user->entitlement()->firstOrCreate([], [
            'plan' => $def['plan'] ?? 'free',
            'daily_interest_limit' => ($def['plan'] ?? 'free') === 'premium' ? 30 : 10,
            'daily_contact_view_limit' => ($def['plan'] ?? 'free') === 'premium' ? 50 : 20,
        ]);

        foreach ($def['educations'] ?? [] as $edu) {
            Education::updateOrCreate(
                ['profile_id' => $profile->id, 'institution' => $edu['institution']],
                [
                    'education_level_id' => $this->educationLevels[$edu['level']] ?? null,
                    'subject' => $edu['subject'],
                    'graduation_year' => $edu['year'],
                ]
            );
        }

        foreach ($def['religion_attrs'] ?? [] as $k => $v) {
            ProfileReligionAttribute::updateOrCreate(
                ['profile_id' => $profile->id, 'key' => $k],
                ['value' => $v]
            );
        }

        if (! empty($def['preference'])) {
            $p = $def['preference'];
            $pref = PartnerPreference::updateOrCreate(
                ['profile_id' => $profile->id],
                [
                    'age_min' => $p['age_min'] ?? null,
                    'age_max' => $p['age_max'] ?? null,
                    'height_min_cm' => $p['height_min_cm'] ?? null,
                    'height_max_cm' => $p['height_max_cm'] ?? null,
                    'marital_statuses' => $p['marital_statuses'] ?? ['never_married'],
                    'religion_id' => isset($p['religion']) ? ($this->religions[$p['religion']] ?? null) : null,
                    'min_education_level_id' => isset($p['min_education_level']) ? ($this->educationLevels[$p['min_education_level']] ?? null) : null,
                    'profession_ids' => array_values(array_filter(array_map(fn ($pr) => $this->professions[$pr] ?? null, $p['professions'] ?? []))),
                    'income_min' => $p['income_min'] ?? null,
                    'district_ids' => array_values(array_filter(array_map(fn ($d) => $this->districts[$d] ?? null, $p['districts'] ?? []))),
                    'other_expectations' => $p['other_expectations'] ?? null,
                ]
            );

            foreach ($p['weights'] ?? [] as $wKey => $wImp) {
                PreferenceWeight::updateOrCreate(
                    ['partner_preference_id' => $pref->id, 'preference_key' => $wKey],
                    ['importance' => $wImp]
                );
            }
        }

        $this->ensureProfilePhoto($profile, $def['initials'], $def['name'], $def['job_title'], $def['color_rgb']);

        if ($admin && ($def['verification_level'] ?? 0) > 0) {
            Verification::updateOrCreate(
                ['user_id' => $user->id, 'level' => $def['verification_level']],
                [
                    'status' => 'approved',
                    'reviewed_by' => $admin->id,
                    'review_note' => 'Verification approved after document and identity confirmation.',
                    'reviewed_at' => now()->subDays(rand(5, 30)),
                ]
            );
        }

        app(ProfileCompletionService::class)->sync($profile);

        return $profile;
    }

    private function ensureProfilePhoto(Profile $profile, string $initials, string $name, string $title, array $rgb): void
    {
        $existing = ProfilePhoto::where('profile_id', $profile->id)->first();
        if ($existing && Storage::disk('local')->exists($existing->path)) {
            return;
        }

        $folder = 'profile-photos/'.$profile->id;
        $filename = 'avatar_primary.jpg';
        $fullPath = $folder.'/'.$filename;

        $im = imagecreatetruecolor(500, 500);

        // Background subtle off-white / light slate
        $bg = imagecolorallocate($im, 248, 250, 252);
        imagefilledrectangle($im, 0, 0, 500, 500, $bg);

        // Elegant outer circle glow
        [$r, $g, $b] = $rgb;
        $glowColor = imagecolorallocate($im, min(255, (int) ($r + 50)), min(255, (int) ($g + 50)), min(255, (int) ($b + 50)));
        imagefilledellipse($im, 250, 210, 240, 240, $glowColor);

        // Core avatar circle
        $mainColor = imagecolorallocate($im, $r, $g, $b);
        imagefilledellipse($im, 250, 210, 220, 220, $mainColor);

        // White inner circle ring
        $ringColor = imagecolorallocate($im, 255, 255, 255);
        imagearc($im, 250, 210, 204, 204, 0, 360, $ringColor);
        imagearc($im, 250, 210, 202, 202, 0, 360, $ringColor);

        // Monogram Initials in center
        $textWhite = imagecolorallocate($im, 255, 255, 255);
        $fontLen = strlen($initials);
        $startX = 250 - ($fontLen * 18);
        imagestring($im, 5, $startX, 195, $initials, $textWhite);

        // Card footer pill banner
        $pillBg = imagecolorallocate($im, 241, 245, 249);
        imagefilledrectangle($im, 40, 360, 460, 450, $pillBg);

        // Accent border on top of pill
        imagefilledrectangle($im, 40, 360, 460, 364, $mainColor);

        // Text details on badge
        $textColor = imagecolorallocate($im, 15, 23, 42);
        $subColor = imagecolorallocate($im, 100, 116, 139);

        $nameDisplay = mb_substr($name, 0, 28);
        $titleDisplay = mb_substr($title, 0, 32);

        $nameX = max(50, 250 - (strlen($nameDisplay) * 5));
        $titleX = max(50, 250 - (strlen($titleDisplay) * 4));

        imagestring($im, 4, (int) $nameX, 382, $nameDisplay, $textColor);
        imagestring($im, 2, (int) $titleX, 412, $titleDisplay, $subColor);

        ob_start();
        imagejpeg($im, null, 95);
        $imageBytes = ob_get_clean();
        imagedestroy($im);

        Storage::disk('local')->put($fullPath, $imageBytes);

        ProfilePhoto::updateOrCreate(
            ['profile_id' => $profile->id, 'position' => 1],
            [
                'disk' => 'local',
                'path' => $fullPath,
                'is_primary' => true,
                'visibility' => Visibility::Public,
                'moderation_status' => PhotoModerationStatus::Approved,
                'width' => 500,
                'height' => 500,
            ]
        );
    }

    private function seedInteractions(?User $tamim, array $profiles): void
    {
        if (! $tamim) {
            return;
        }

        $females = array_values(array_filter($profiles, fn ($p) => $p->gender === Gender::Female));
        $males = array_values(array_filter($profiles, fn ($p) => $p->gender === Gender::Male));

        if (count($females) < 3) {
            return;
        }

        // Dr. Nusrat (Profile 0), Tahmina (Profile 1), Syeda Maliha (Profile 2), Sadia (Profile 3)
        $drNusrat = $females[0];
        $tahmina = $females[1];
        $maliha = $females[2];
        $sadia = $females[3] ?? null;

        // 1. Shortlists for Tamim
        Shortlist::firstOrCreate(
            ['user_id' => $tamim->id, 'profile_id' => $drNusrat->id],
            ['note' => 'Very impressive background in medicine and shared ethical family values.']
        );
        Shortlist::firstOrCreate(
            ['user_id' => $tamim->id, 'profile_id' => $tahmina->id],
            ['note' => 'Creative product designer with great aesthetic sense and humble nature.']
        );
        Shortlist::firstOrCreate(
            ['user_id' => $tamim->id, 'profile_id' => $maliha->id],
            ['note' => 'Accomplished academic lecturer with cultural and musical depth.']
        );

        // Other users shortlisting Tamim's profile
        if ($tamim->profile) {
            Shortlist::firstOrCreate(
                ['user_id' => $drNusrat->user_id, 'profile_id' => $tamim->profile->id],
                ['note' => 'Good match: BUET engineering background, established career in Dhaka.']
            );
        }

        // 2. Interest: Tamim <-> Dr. Nusrat (ACCEPTED + Conversation + Messages)
        $interestNusrat = Interest::updateOrCreate(
            ['sender_id' => $tamim->id, 'receiver_id' => $drNusrat->user_id],
            [
                'status' => InterestStatus::Accepted->value,
                'message' => 'Assalamu Alaikum Dr. Nusrat. It is a pleasure to connect with you. I was very impressed reading your background and values.',
                'expires_at' => now()->addDays(7),
                'responded_at' => now()->subHours(6),
            ]
        );

        InterestEvent::firstOrCreate(
            ['interest_id' => $interestNusrat->id, 'to_status' => 'sent'],
            ['actor_id' => $tamim->id, 'event' => 'sent', 'from_status' => null, 'created_at' => now()->subDay()]
        );
        InterestEvent::firstOrCreate(
            ['interest_id' => $interestNusrat->id, 'to_status' => 'accepted'],
            ['actor_id' => $drNusrat->user_id, 'event' => 'accepted', 'from_status' => 'pending', 'created_at' => now()->subHours(6)]
        );

        // Conversation between Tamim and Dr. Nusrat
        $convUserOne = min($tamim->id, $drNusrat->user_id);
        $convUserTwo = max($tamim->id, $drNusrat->user_id);

        $conversation = Conversation::firstOrCreate(
            ['user_one_id' => $convUserOne, 'user_two_id' => $convUserTwo],
            ['interest_id' => $interestNusrat->id, 'last_message_at' => now()->subHours(1)]
        );

        $messages = [
            [
                'sender_id' => $tamim->id,
                'body' => 'Assalamu Alaikum Dr. Nusrat! Thank you for accepting my interest. I hope your hospital rounds went well today.',
                'created_at' => now()->subHours(5),
            ],
            [
                'sender_id' => $drNusrat->user_id,
                'body' => 'Wa Alaikum Assalam! Thank you for reaching out. Yes, hospital was quite busy, but rewarding as always. I went through your profile as well — your career in tech and thoughts on family really resonated with me.',
                'created_at' => now()->subHours(4),
            ],
            [
                'sender_id' => $tamim->id,
                'body' => 'Alhamdulillah, that is wonderful to hear. I would love for our families to connect whenever convenient for you.',
                'created_at' => now()->subHours(2),
            ],
            [
                'sender_id' => $drNusrat->user_id,
                'body' => 'InshaAllah! Friday afternoon after Jummah would be convenient for my parents as well. Let us coordinate then.',
                'created_at' => now()->subHours(1),
            ],
        ];

        foreach ($messages as $m) {
            Message::firstOrCreate(
                ['conversation_id' => $conversation->id, 'sender_id' => $m['sender_id'], 'body' => $m['body']],
                ['created_at' => $m['created_at'], 'read_at' => now()]
            );
        }

        // 3. Interest: Tahmina -> Tamim (PENDING incoming)
        $interestTahmina = Interest::updateOrCreate(
            ['sender_id' => $tahmina->user_id, 'receiver_id' => $tamim->id],
            [
                'status' => InterestStatus::Pending->value,
                'message' => 'Assalamu Alaikum! Your profile caught my eye. We seem to share similar creative interests and tech backgrounds. Looking forward to your response.',
                'expires_at' => now()->addDays(5),
                'responded_at' => null,
            ]
        );
        InterestEvent::firstOrCreate(
            ['interest_id' => $interestTahmina->id, 'to_status' => 'sent'],
            ['actor_id' => $tahmina->user_id, 'event' => 'sent', 'from_status' => null, 'created_at' => now()->subHours(8)]
        );

        // 4. Interest: Tamim -> Syeda Maliha (PENDING outgoing)
        $interestMaliha = Interest::updateOrCreate(
            ['sender_id' => $tamim->id, 'receiver_id' => $maliha->user_id],
            [
                'status' => InterestStatus::Pending->value,
                'message' => 'Assalamu Alaikum! I read your profile with keen interest. Your academic passion and cultural interests are truly admirable.',
                'expires_at' => now()->addDays(6),
                'responded_at' => null,
            ]
        );
        InterestEvent::firstOrCreate(
            ['interest_id' => $interestMaliha->id, 'to_status' => 'sent'],
            ['actor_id' => $tamim->id, 'event' => 'sent', 'from_status' => null, 'created_at' => now()->subHours(12)]
        );

        // 5. Profile views for Tamim's profile
        if ($tamim->profile) {
            foreach ([$drNusrat, $tahmina, $maliha, $sadia] as $idx => $viewer) {
                if ($viewer) {
                    ProfileView::firstOrCreate(
                        ['profile_id' => $tamim->profile->id, 'viewer_id' => $viewer->user_id],
                        ['viewed_at' => now()->subHours(($idx + 1) * 3)]
                    );
                }
            }
        }

        // 6. In-app notifications for Tamim
        Notification::firstOrCreate(
            ['user_id' => $tamim->id, 'type' => 'new_interest'],
            [
                'title' => 'New Interest Received',
                'message' => 'Tahmina Akter Snigdha has expressed interest in your profile.',
                'data' => ['sender_id' => $tahmina->user_id],
                'read_at' => null,
            ]
        );
        Notification::firstOrCreate(
            ['user_id' => $tamim->id, 'type' => 'interest_accepted'],
            [
                'title' => 'Interest Accepted',
                'message' => 'Dr. Nusrat Jahan Raisa accepted your interest request! You can now chat.',
                'data' => ['partner_id' => $drNusrat->user_id],
                'read_at' => now()->subHours(5),
            ]
        );
        Notification::firstOrCreate(
            ['user_id' => $tamim->id, 'type' => 'new_message'],
            [
                'title' => 'New Message',
                'message' => 'Dr. Nusrat Jahan Raisa sent you a message.',
                'data' => ['conversation_id' => $conversation->id],
                'read_at' => null,
            ]
        );
    }

    /**
     * Complete specifications for 16 realistic, attractive, culturally accurate Bangladeshi profiles.
     */
    private function getCandidateDefinitions(): array
    {
        return [
            // -------------------------------------------------------------
            // FEMALE PROFILES
            // -------------------------------------------------------------
            [
                'name' => 'Dr. Nusrat Jahan Raisa',
                'name_bn' => 'ডাঃ নুসরাত জাহান রাইসা',
                'email' => 'nusrat.raisa@matrimony.test',
                'phone' => '01712000001',
                'dob' => '1999-04-12',
                'gender' => Gender::Female,
                'height_cm' => 162,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'Dhaka Medical College (DMC)',
                'subject' => 'Pediatrics (FCPS Part-1 Resident)',
                'graduation_year' => 2022,
                'profession' => 'doctor',
                'job_title' => 'Medical Officer (Pediatrics)',
                'organization' => 'BIRDEM General Hospital & Research Institute',
                'income_min' => 95000,
                'income_max' => 140000,
                'work_location' => 'Shahbagh, Dhaka',
                'father_occupation' => 'Chief Scientific Officer (BARI, Retired)',
                'mother_occupation' => 'Associate Professor of English (Eden Mohila College)',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Dhanmondi 7/A, Dhaka',
                'family_description' => 'Respected, academically oriented family with deep moral integrity and strong traditional values.',
                'headline' => 'Pediatrician at BIRDEM | Passionate about healing, poetry & quiet evenings',
                'about' => "I am a warm, compassionate pediatrician who finds genuine joy in working with children and making a positive difference in people's lives. Born and raised in an academically inclined family in Dhanmondi, I value emotional maturity, spiritual grounding, and mutual respect. Outside medicine, I love baking artisanal desserts, reading Bengali literature (Humayun Ahmed and Sunil Gangopadhyay), and tending to my balcony garden. I pray regularly and strive to maintain balance between professional aspirations and Islamic ethics. Looking for an educated, kind-hearted gentleman with integrity, a progressive outlook, and strong respect for family bonds.",
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Baking', 'Calligraphy', 'Balcony Gardening', 'Bengali Literature'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni (Hanafi)',
                    'prayer_habit' => 'Always (5 times daily)',
                    'hijab' => 'Observes modest dress and hijab outside',
                ],
                'educations' => [
                    ['institution' => 'Viqarunnisa Noon College', 'level' => 'hsc', 'subject' => 'Science', 'year' => 2016],
                    ['institution' => 'Dhaka Medical College', 'level' => 'bachelor', 'subject' => 'MBBS', 'year' => 2022],
                    ['institution' => 'BCPS (Bangladesh College of Physicians & Surgeons)', 'level' => 'master', 'subject' => 'FCPS Pediatrics Residency', 'year' => 2024],
                ],
                'preference' => [
                    'age_min' => 28,
                    'age_max' => 34,
                    'height_min_cm' => 170,
                    'height_max_cm' => 185,
                    'marital_statuses' => ['never_married'],
                    'religion' => 'islam',
                    'min_education_level' => 'bachelor',
                    'professions' => ['doctor', 'software_developer', 'engineer', 'banker', 'government_service'],
                    'districts' => ['Dhaka', 'Chattogram'],
                    'other_expectations' => 'Looking for an established, understanding gentleman with high integrity and spiritual grounding.',
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                        'age_range' => Importance::DealBreaker,
                        'education' => Importance::NiceToHave,
                        'profession' => Importance::NiceToHave,
                    ],
                ],
                'initials' => 'NR',
                'color_rgb' => [225, 29, 72],
                'verification_level' => VerificationLevel::Selfie->value,
                'plan' => 'premium',
            ],
            [
                'name' => 'Tahmina Akter Snigdha',
                'name_bn' => 'তাহমিনা আক্তার স্নিগ্ধা',
                'email' => 'tahmina.snigdha@matrimony.test',
                'phone' => '01712000002',
                'dob' => '2000-01-15',
                'gender' => Gender::Female,
                'height_cm' => 165,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'islam',
                'education_level' => 'bachelor',
                'institution' => 'BRAC University',
                'subject' => 'Computer Science & Engineering',
                'graduation_year' => 2022,
                'profession' => 'software_developer',
                'job_title' => 'Senior Product Designer (UI/UX)',
                'organization' => 'Remotebase / Silicon Valley Design Agency',
                'income_min' => 190000,
                'income_max' => 270000,
                'work_location' => 'Remote (Based in Uttara, Dhaka)',
                'father_occupation' => 'Superintending Engineer (PWD, Retired)',
                'mother_occupation' => 'High School Headmistress',
                'siblings_count' => 2,
                'family_type' => 'nuclear',
                'family_location' => 'Uttara Sector 7, Dhaka',
                'family_description' => 'Educated, warm, and supportive family who prioritize honesty, hospitality, and mutual encouragement.',
                'headline' => 'Product Designer for US Startups | BRACU Alum | Creative mind, plant mom & foodie',
                'about' => 'I am a cheerful, creative soul working remotely as a senior UI/UX designer for high-growth tech startups. I love thoughtful design, calm minimalist spaces, and genuine laughter. My weekends usually involve brewing tea, experimenting with Italian and Thai recipes, watercolor painting, and documenting heritage architecture in Old Dhaka. I pray regularly, dress modestly with hijab, and value sincerity above everything. Seeking an emotionally intelligent, gentle-hearted partner who loves meaningful conversations, supports mutual ambitions, and cherishes building a harmonious home.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Digital Illustration', 'Cooking', 'Balcony Plants', 'Reading'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni (Hanafi)',
                    'prayer_habit' => 'Regular (prays 5 times)',
                    'hijab' => 'Maintains hijab gracefully',
                ],
                'educations' => [
                    ['institution' => 'Rajuk Uttara Model College', 'level' => 'hsc', 'subject' => 'Science', 'year' => 2018],
                    ['institution' => 'BRAC University', 'level' => 'bachelor', 'subject' => 'CSE', 'year' => 2022],
                ],
                'preference' => [
                    'age_min' => 27,
                    'age_max' => 33,
                    'height_min_cm' => 172,
                    'height_max_cm' => 185,
                    'religion' => 'islam',
                    'min_education_level' => 'bachelor',
                    'professions' => ['software_developer', 'engineer', 'business', 'doctor'],
                    'districts' => ['Dhaka'],
                    'other_expectations' => 'A supportive partner who communicates openly, possesses high ethical standards, and loves to laugh.',
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                        'age_range' => Importance::NiceToHave,
                    ],
                ],
                'initials' => 'TS',
                'color_rgb' => [234, 88, 12],
                'verification_level' => VerificationLevel::Nid->value,
            ],
            [
                'name' => 'Syeda Maliha Anjum',
                'name_bn' => 'সৈয়দা মালিহা আনজুম',
                'email' => 'maliha.anjum@matrimony.test',
                'phone' => '01712000003',
                'dob' => '1998-06-25',
                'gender' => Gender::Female,
                'height_cm' => 167,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'University of Warwick (UK) & IBA, University of Dhaka',
                'subject' => 'MSc in Economics & Finance',
                'graduation_year' => 2022,
                'profession' => 'teacher',
                'job_title' => 'Senior Lecturer in Economics',
                'organization' => 'North South University (NSU)',
                'income_min' => 140000,
                'income_max' => 195000,
                'work_location' => 'Bashundhara R/A, Dhaka',
                'father_occupation' => 'Managing Director, Horizon Apparels Ltd.',
                'mother_occupation' => 'Senior Principal, Scholastica School (Retired)',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Baridhara DOHS, Dhaka',
                'family_description' => 'Cultured, aristocratic Syeda family in Baridhara with deep heritage, philanthropic values, and focus on higher education.',
                'headline' => 'Economics Lecturer at NSU | Warwick & IBA Alum | Rabindra Sangeet & literature lover',
                'about' => 'I am an economist and university lecturer who is passionate about education, development policy, and classical arts. After completing my BBA at IBA (DU), I spent two wonderful years in England pursuing my master’s at Warwick before returning home to teach. Trained in Rabindra Sangeet for seven years at Chhayanaut, music is an essential part of my life. I appreciate intellectual curiosity, good manners, warmth, and spiritual grounding. Looking for a cultured, well-educated gentleman with strong integrity, ambitious career goals, and deep respect for family.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Rabindra Sangeet', 'Economic Policy Reading', 'Traveling Europe & Asia', 'Hostessing'],
                    'languages' => ['Bangla', 'English', 'French (Elementary)'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni',
                    'prayer_habit' => 'Regular',
                    'hijab' => 'Dresses modestly and elegantly',
                ],
                'educations' => [
                    ['institution' => 'IBA, University of Dhaka', 'level' => 'bachelor', 'subject' => 'BBA in Finance', 'year' => 2020],
                    ['institution' => 'University of Warwick (United Kingdom)', 'level' => 'master', 'subject' => 'MSc in Economics', 'year' => 2022],
                ],
                'preference' => [
                    'age_min' => 28,
                    'age_max' => 35,
                    'height_min_cm' => 173,
                    'height_max_cm' => 188,
                    'religion' => 'islam',
                    'min_education_level' => 'master',
                    'professions' => ['engineer', 'software_developer', 'banker', 'government_service', 'business'],
                    'districts' => ['Dhaka'],
                    'other_expectations' => 'Cultured background, graduate from a reputed institution, respectful of working women.',
                    'weights' => [
                        'education' => Importance::DealBreaker,
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'MA',
                'color_rgb' => [124, 58, 237],
                'verification_level' => VerificationLevel::Manual->value,
                'plan' => 'premium',
            ],
            [
                'name' => 'Sadia Islam Hridi',
                'name_bn' => 'সাদিয়া ইসলাম হৃদি',
                'email' => 'sadia.hridi@matrimony.test',
                'phone' => '01712000004',
                'dob' => '2001-09-18',
                'gender' => Gender::Female,
                'height_cm' => 160,
                'marital_status' => 'never_married',
                'district' => 'Chattogram',
                'religion' => 'islam',
                'education_level' => 'bachelor',
                'institution' => 'University of Chittagong',
                'subject' => 'Computer Science & Engineering',
                'graduation_year' => 2023,
                'profession' => 'software_developer',
                'job_title' => 'QA Automation Lead',
                'organization' => 'Brain Station 23',
                'income_min' => 110000,
                'income_max' => 160000,
                'work_location' => 'Agrabad, Chattogram',
                'father_occupation' => 'Chief Marine Engineer, Chittagong Port Authority',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Panchlaish R/A, Chattogram',
                'family_description' => 'Close-knit, cheerful family from Chattogram with respected reputation and religious values.',
                'headline' => 'QA Lead at Brain Station 23 | CU CSE Alum | Cheerful, foodie & badminton enthusiast',
                'about' => 'Hello! I am a cheerful, energetic software engineer who believes life should be lived with enthusiasm and kindness. I love testing scalable software by day and cooking authentic Chattagonian dishes by evening. I play competitive badminton, love sea breezes along Patenga beach, and value deep familial connections. I seek an empathetic, ambitious life partner who will be my best friend, confidant, and partner through all life adventures.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Badminton', 'Culinary Cooking', 'Beach Walking', 'Photography'],
                    'languages' => ['Bangla', 'English', 'Chatgaya'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni',
                    'prayer_habit' => 'Regular',
                    'hijab' => 'Maintains hijab',
                ],
                'educations' => [
                    ['institution' => 'Chittagong College', 'level' => 'hsc', 'subject' => 'Science', 'year' => 2018],
                    ['institution' => 'University of Chittagong', 'level' => 'bachelor', 'subject' => 'BSc in CSE', 'year' => 2023],
                ],
                'preference' => [
                    'age_min' => 25,
                    'age_max' => 31,
                    'height_min_cm' => 168,
                    'height_max_cm' => 183,
                    'religion' => 'islam',
                    'districts' => ['Chattogram', 'Dhaka'],
                    'professions' => ['software_developer', 'engineer', 'banker', 'doctor'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'SH',
                'color_rgb' => [13, 148, 136],
                'verification_level' => VerificationLevel::Nid->value,
            ],
            [
                'name' => 'Ayesha Siddiqua Meem',
                'name_bn' => 'আয়েশা সিদ্দিকা মীম',
                'email' => 'ayesha.meem@matrimony.test',
                'phone' => '01712000005',
                'dob' => '2000-05-30',
                'gender' => Gender::Female,
                'height_cm' => 163,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'islam',
                'education_level' => 'bachelor',
                'institution' => 'BUET (Bangladesh University of Engineering and Technology)',
                'subject' => 'Bachelor of Architecture (B.Arch)',
                'graduation_year' => 2023,
                'profession' => 'engineer',
                'job_title' => 'Architect & Spatial Designer',
                'organization' => 'Vitti Sthapati Vrindo Ltd.',
                'income_min' => 120000,
                'income_max' => 175000,
                'work_location' => 'Banani, Dhaka',
                'father_occupation' => 'Chief Engineer, Roads & Highways Department (Retired)',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 2,
                'family_type' => 'nuclear',
                'family_location' => 'Dhanmondi, Dhaka (Ancestral home: Cumilla)',
                'family_description' => 'Noble, cultured family with deep love for craftsmanship, education, and moral righteousness.',
                'headline' => 'Architect at Vitti Sthapati | BUET B.Arch | Heritage sketching, design & plant lover',
                'about' => 'I am an architect with an eye for elegance, harmony, and sustainable design. Graduating from BUET taught me perseverance, creativity, and the power of thoughtful spaces. I am gentle-natured, soft-spoken, and value peaceful family life. I love sketching historical terracotta monuments, nurturing indoor plants, and baking on quiet weekend mornings. As a practicing Muslim, I wear hijab and observe Islamic ethics. I am looking for a grounded, educated, kind gentleman with high values and mutual respect.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Architectural Sketching', 'Urban Gardening', 'Baking', 'Museum Visits'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni (Hanafi)',
                    'prayer_habit' => 'Always (5 times daily)',
                    'hijab' => 'Maintains proper hijab',
                ],
                'educations' => [
                    ['institution' => 'Holy Cross College', 'level' => 'hsc', 'subject' => 'Science', 'year' => 2017],
                    ['institution' => 'BUET', 'level' => 'bachelor', 'subject' => 'Architecture', 'year' => 2023],
                ],
                'preference' => [
                    'age_min' => 26,
                    'age_max' => 32,
                    'height_min_cm' => 170,
                    'height_max_cm' => 185,
                    'religion' => 'islam',
                    'professions' => ['engineer', 'software_developer', 'doctor', 'government_service'],
                    'districts' => ['Dhaka', 'Cumilla'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'AM',
                'color_rgb' => [5, 150, 105],
                'verification_level' => VerificationLevel::Selfie->value,
            ],
            [
                'name' => 'Nusrat Parveen Nabila',
                'name_bn' => 'নুসরাত পারভীন নাবিলা',
                'email' => 'nusrat.nabila@matrimony.test',
                'phone' => '01712000006',
                'dob' => '1997-03-22',
                'gender' => Gender::Female,
                'height_cm' => 164,
                'marital_status' => 'never_married',
                'district' => 'Sylhet',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'Shahjalal University of Science and Technology (SUST)',
                'subject' => 'BBA & MBA in Banking and Finance',
                'graduation_year' => 2021,
                'profession' => 'banker',
                'job_title' => 'Senior Credit Risk Analyst',
                'organization' => 'Eastern Bank Ltd. (EBL)',
                'income_min' => 115000,
                'income_max' => 165000,
                'work_location' => 'Zindabazar, Sylhet',
                'father_occupation' => 'Deputy General Manager (Agrani Bank, Retired)',
                'mother_occupation' => 'Senior Teacher, Government Girls High School',
                'siblings_count' => 2,
                'family_type' => 'nuclear',
                'family_location' => 'Amberkhana, Sylhet',
                'family_description' => 'Respected banking family in Sylhet city known for honesty, warmth, and strong spiritual identity.',
                'headline' => 'Senior Credit Analyst at EBL | SUST Alum | Organized, family-oriented & home cook',
                'about' => 'I am a credit risk analyst with a calm, analytical, and friendly disposition. I balance my professional responsibilities with a warm, caring family life. I love cooking traditional Sylheti dishes, hosting family get-togethers, and traveling around the tea gardens of Sreemangal. Raised in a devout, loving household, I hold traditional Islamic values close to my heart. Seeking an established, respectful gentleman who values family warmth, honesty, and shared spiritual growth.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Sylheti Cuisine Cooking', 'Reading Finance Journals', 'Gardening', 'Travel'],
                    'languages' => ['Bangla', 'English', 'Sylheti'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni (Hanafi)',
                    'prayer_habit' => 'Always',
                    'hijab' => 'Maintains hijab gracefully',
                ],
                'educations' => [
                    ['institution' => 'Sylhet Govt. Women College', 'level' => 'hsc', 'subject' => 'Commerce', 'year' => 2015],
                    ['institution' => 'SUST', 'level' => 'bachelor', 'subject' => 'BBA Finance', 'year' => 2019],
                    ['institution' => 'SUST', 'level' => 'master', 'subject' => 'MBA Finance', 'year' => 2021],
                ],
                'preference' => [
                    'age_min' => 29,
                    'age_max' => 35,
                    'height_min_cm' => 170,
                    'height_max_cm' => 184,
                    'religion' => 'islam',
                    'districts' => ['Sylhet', 'Dhaka'],
                    'professions' => ['banker', 'business', 'engineer', 'software_developer', 'doctor'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'NN',
                'color_rgb' => [147, 51, 234],
                'verification_level' => VerificationLevel::Nid->value,
            ],
            [
                'name' => 'Farzana Haque Bristi',
                'name_bn' => 'ফারজানা হক বৃষ্টি',
                'email' => 'farzana.bristi@matrimony.test',
                'phone' => '01712000007',
                'dob' => '1998-08-14',
                'gender' => Gender::Female,
                'height_cm' => 166,
                'marital_status' => 'never_married',
                'district' => 'Bogura',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'University of Dhaka',
                'subject' => 'MSS in Public Administration (BCS Cadre)',
                'graduation_year' => 2021,
                'profession' => 'government_service',
                'job_title' => 'Assistant Commissioner & Executive Magistrate',
                'organization' => 'Government of Bangladesh (BCS Administration)',
                'income_min' => 90000,
                'income_max' => 135000,
                'work_location' => 'Bogura District Administration',
                'father_occupation' => 'Joint Secretary, Ministry of Home Affairs (Retired)',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Joleshwaritola, Bogura (Dhaka residence: Mirpur DOHS)',
                'family_description' => 'Prestigious civil service family upholding high ethical standards, dignity, and public service.',
                'headline' => 'Assistant Commissioner (BCS Admin) | DU Alum | Dignified, bookworm & music lover',
                'about' => 'Serving as an administrative officer has reinforced my dedication to social justice, integrity, and humility. Despite a demanding profession, I am a very warm, grounded, and compassionate person at home. I love rainy afternoons, classic Bengali novels (Bibhutibhushan and Tarasankar), and sharing laughter over home-brewed tea. Looking for an educated, understanding gentleman with high moral stature, emotional depth, and supportive spirit.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Reading Classical Fiction', 'Public Policy Writing', 'Gardening'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni',
                    'prayer_habit' => 'Regular',
                    'hijab' => 'Dresses modestly and formally',
                ],
                'educations' => [
                    ['institution' => 'Holy Cross College', 'level' => 'hsc', 'subject' => 'Humanities', 'year' => 2016],
                    ['institution' => 'University of Dhaka', 'level' => 'bachelor', 'subject' => 'Public Administration', 'year' => 2020],
                    ['institution' => 'University of Dhaka', 'level' => 'master', 'subject' => 'Public Administration', 'year' => 2021],
                ],
                'preference' => [
                    'age_min' => 28,
                    'age_max' => 35,
                    'height_min_cm' => 172,
                    'height_max_cm' => 186,
                    'religion' => 'islam',
                    'professions' => ['government_service', 'engineer', 'software_developer', 'doctor', 'banker'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'FB',
                'color_rgb' => [217, 119, 6],
                'verification_level' => VerificationLevel::Manual->value,
            ],
            [
                'name' => 'Priyanka Sengupta',
                'name_bn' => 'প্রিয়াঙ্কা সেনগুপ্ত',
                'email' => 'priyanka.sengupta@matrimony.test',
                'phone' => '01712000008',
                'dob' => '1999-12-08',
                'gender' => Gender::Female,
                'height_cm' => 161,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'hindu',
                'education_level' => 'master',
                'institution' => 'University of Dhaka (FBS)',
                'subject' => 'BBA & MBA in Marketing',
                'graduation_year' => 2023,
                'profession' => 'private_service',
                'job_title' => 'Senior Brand Manager',
                'organization' => 'Unilever Bangladesh',
                'income_min' => 155000,
                'income_max' => 230000,
                'work_location' => 'Gulshan 1, Dhaka',
                'father_occupation' => 'Senior Partner, Chartered Accountants Firm',
                'mother_occupation' => 'Principal, Lalmatia Mohila College',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Lalmatia, Dhaka',
                'family_description' => 'Culturally vibrant, liberal Hindu family in Lalmatia with deep love for literature, arts, and professional excellence.',
                'headline' => 'Senior Brand Manager at Unilever | DU FBS Alum | Classical dancer & creative thinker',
                'about' => 'I am an energetic brand marketer with a deep love for storytelling and consumer psychology. Trained in classical Bharatnatyam for over a decade, dance has given me grace, poise, and expression. Raised in an open-minded Hindu family where education, kindness, and independent thinking are cherished. I enjoy exploring art exhibitions, hosting dinner parties, and collecting vintage vinyl records. Looking for a progressive, accomplished, and warm partner with high values and mutual respect.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'omnivore',
                    'hobbies' => ['Classical Dance', 'Art Appreciation', 'Reading Fiction', 'Culinary Cooking'],
                    'languages' => ['Bangla', 'English', 'Hindi'],
                ],
                'religion_attrs' => [
                    'caste' => 'Brahmin',
                    'gotra' => 'Sandilya',
                ],
                'educations' => [
                    ['institution' => 'Viqarunnisa Noon College', 'level' => 'hsc', 'subject' => 'Commerce', 'year' => 2017],
                    ['institution' => 'University of Dhaka', 'level' => 'bachelor', 'subject' => 'BBA Marketing', 'year' => 2021],
                    ['institution' => 'University of Dhaka', 'level' => 'master', 'subject' => 'MBA Marketing', 'year' => 2023],
                ],
                'preference' => [
                    'age_min' => 27,
                    'age_max' => 33,
                    'height_min_cm' => 170,
                    'height_max_cm' => 184,
                    'religion' => 'hindu',
                    'professions' => ['software_developer', 'engineer', 'banker', 'private_service', 'doctor'],
                    'districts' => ['Dhaka'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'PS',
                'color_rgb' => [37, 99, 235],
                'verification_level' => VerificationLevel::Selfie->value,
            ],

            // -------------------------------------------------------------
            // MALE PROFILES
            // -------------------------------------------------------------
            [
                'name' => 'Engr. Tanvir Ahmed Chowdhury',
                'name_bn' => 'প্রকৌশলী তানভীর আহমেদ চৌধুরী',
                'email' => 'tanvir.chowdhury@matrimony.test',
                'phone' => '01712000009',
                'dob' => '1996-08-20',
                'gender' => Gender::Male,
                'height_cm' => 178,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'islam',
                'education_level' => 'bachelor',
                'institution' => 'BUET',
                'subject' => 'Computer Science & Engineering',
                'graduation_year' => 2019,
                'profession' => 'software_developer',
                'job_title' => 'Staff Cloud Architect & Engineering Lead',
                'organization' => 'bKash Limited',
                'income_min' => 230000,
                'income_max' => 340000,
                'work_location' => 'Gulshan 2, Dhaka',
                'father_occupation' => 'Additional Secretary, Ministry of Finance (Retired)',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Banani, Dhaka (Ancestral home: Feni)',
                'family_description' => 'Cultured, highly educated family with distinguished civil service and engineering pedigree.',
                'headline' => 'Staff Engineer at bKash | BUET CSE | Coffee enthusiast, tech builder & traveler',
                'about' => 'I am an ambitious tech professional with a deep appreciation for good conversations, architecture, and technology. After graduating from BUET, I have spent the last seven years building scalable cloud platforms. I believe life is best lived with curiosity, empathy, and laughter. In my spare time, I brew specialty pour-over coffee, play acoustic guitar, work out, and travel whenever I can. Raised in a loving, open-minded Muslim family that values education and character above all. Seeking an intelligent, warm, and graceful life partner who values personal growth and wants to build a harmonious home together.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Specialty Coffee', 'Acoustic Guitar', 'Gym & Fitness', 'Road Trips'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni (Hanafi)',
                    'prayer_habit' => 'Regular (prays 5 times & Jummah)',
                    'beard' => 'Trimmed neat beard',
                ],
                'educations' => [
                    ['institution' => 'Notre Dame College', 'level' => 'hsc', 'subject' => 'Science', 'year' => 2014],
                    ['institution' => 'BUET', 'level' => 'bachelor', 'subject' => 'CSE', 'year' => 2019],
                ],
                'preference' => [
                    'age_min' => 23,
                    'age_max' => 28,
                    'height_min_cm' => 158,
                    'height_max_cm' => 170,
                    'religion' => 'islam',
                    'districts' => ['Dhaka', 'Chattogram'],
                    'professions' => ['doctor', 'software_developer', 'teacher', 'banker', 'private_service'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                        'age_range' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'TC',
                'color_rgb' => [30, 58, 138],
                'verification_level' => VerificationLevel::Selfie->value,
                'plan' => 'premium',
            ],
            [
                'name' => 'Dr. Kazi Farhan Masud',
                'name_bn' => 'ডাঃ কাজী ফারহান মাসুদ',
                'email' => 'farhan.masud@matrimony.test',
                'phone' => '01712000010',
                'dob' => '1995-03-10',
                'gender' => Gender::Male,
                'height_cm' => 180,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'Dhaka Medical College (DMC)',
                'subject' => 'FCPS in Cardiology & MBBS',
                'graduation_year' => 2021,
                'profession' => 'doctor',
                'job_title' => 'Consultant Cardiologist',
                'organization' => 'National Heart Foundation Hospital',
                'income_min' => 190000,
                'income_max' => 290000,
                'work_location' => 'Mirpur / Gulshan, Dhaka',
                'father_occupation' => 'Professor of Medicine, DMC (Retired)',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Gulshan 2, Dhaka',
                'family_description' => 'Eminent medical family in Dhaka, respected for professional devotion and philanthropic health camps.',
                'headline' => 'Cardiologist at National Heart Foundation | DMC Alum | Caring, disciplined & calm soul',
                'about' => 'I am a dedicated physician with a passion for cardiology and clinical research. Medicine has taught me patience, humility, and deep empathy for everyday blessings. Outside hospital rounds, I enjoy swimming, playing badminton, exploring historical books, and spending quality time with my family. I hold traditional family values close to my heart while embracing a progressive, balanced lifestyle. Looking for a compassionate, educated, and cheerful partner who values mutual understanding, kindness, and building a happy family together.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Swimming', 'Badminton', 'Historical Reading', 'Classical Music'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni (Hanafi)',
                    'prayer_habit' => 'Always (5 times daily)',
                    'beard' => 'Trimmed beard',
                ],
                'educations' => [
                    ['institution' => 'Notre Dame College', 'level' => 'hsc', 'subject' => 'Science', 'year' => 2013],
                    ['institution' => 'Dhaka Medical College', 'level' => 'bachelor', 'subject' => 'MBBS', 'year' => 2019],
                    ['institution' => 'BCPS', 'level' => 'master', 'subject' => 'FCPS Cardiology', 'year' => 2023],
                ],
                'preference' => [
                    'age_min' => 24,
                    'age_max' => 29,
                    'height_min_cm' => 160,
                    'height_max_cm' => 172,
                    'religion' => 'islam',
                    'professions' => ['doctor', 'teacher', 'software_developer', 'banker'],
                    'districts' => ['Dhaka'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'FM',
                'color_rgb' => [51, 65, 85],
                'verification_level' => VerificationLevel::Manual->value,
            ],
            [
                'name' => 'Arifur Rahman Siddique',
                'name_bn' => 'আরিফুর রহমান সিদ্দিকী',
                'email' => 'arifur.siddique@matrimony.test',
                'phone' => '01712000011',
                'dob' => '1997-11-05',
                'gender' => Gender::Male,
                'height_cm' => 175,
                'marital_status' => 'never_married',
                'district' => 'Chattogram',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'University of Dhaka',
                'subject' => 'MSS in International Relations',
                'graduation_year' => 2021,
                'profession' => 'government_service',
                'job_title' => 'Assistant Commissioner & Executive Magistrate',
                'organization' => 'Government of Bangladesh (BCS Cadre)',
                'income_min' => 90000,
                'income_max' => 130000,
                'work_location' => 'Chattogram Divisional Commissionerate',
                'father_occupation' => 'District & Sessions Judge (Retired)',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 2,
                'family_type' => 'nuclear',
                'family_location' => 'Khulshi, Chattogram',
                'family_description' => 'Honorable judicial family in Chattogram known for integrity, literary taste, and high social standing.',
                'headline' => 'BCS Administration Cadre Officer | DU Alum | Dignified, book lover & community-driven',
                'about' => 'I serve as a civil servant with a strong sense of public responsibility and love for my motherland. Graduated with honors and masters from Dhaka University, where I actively participated in national debating championships. I appreciate simplicity, high moral values, and intellectual depth. In my free time, I love book collecting, visiting historical archeological sites, playing lawn tennis, and writing essays on public policy. Raised in a respected, cultured family in Khulshi, Chattogram. Looking for an educated, graceful lady who values mutual respect and family values.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Debating', 'Lawn Tennis', 'Book Collecting', 'Heritage Travel'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni (Hanafi)',
                    'prayer_habit' => 'Always',
                    'beard' => 'Clean shaven',
                ],
                'educations' => [
                    ['institution' => 'Chittagong College', 'level' => 'hsc', 'subject' => 'Humanities', 'year' => 2015],
                    ['institution' => 'University of Dhaka', 'level' => 'bachelor', 'subject' => 'International Relations', 'year' => 2019],
                    ['institution' => 'University of Dhaka', 'level' => 'master', 'subject' => 'International Relations', 'year' => 2021],
                ],
                'preference' => [
                    'age_min' => 23,
                    'age_max' => 28,
                    'height_min_cm' => 158,
                    'height_max_cm' => 170,
                    'religion' => 'islam',
                    'districts' => ['Chattogram', 'Dhaka'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'AS',
                'color_rgb' => [6, 95, 70],
                'verification_level' => VerificationLevel::Selfie->value,
            ],
            [
                'name' => 'Zubair Al Mahmud',
                'name_bn' => 'জুবায়ের আল মাহমুদ',
                'email' => 'zubair.mahmud@matrimony.test',
                'phone' => '01712000012',
                'dob' => '1998-02-14',
                'gender' => Gender::Male,
                'height_cm' => 182,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'IBA, University of Dhaka & North South University',
                'subject' => 'MBA in Marketing & Technology Management',
                'graduation_year' => 2022,
                'profession' => 'private_service',
                'job_title' => 'Senior Product Manager',
                'organization' => 'Pathao Limited',
                'income_min' => 195000,
                'income_max' => 295000,
                'work_location' => 'Gulshan 1, Dhaka',
                'father_occupation' => 'Senior Partner, Chartered Accountants Firm',
                'mother_occupation' => 'Professor of Economics, DU',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Uttara Sector 4, Dhaka',
                'family_description' => 'Dynamic, forward-thinking corporate family committed to education, business leadership, and mutual respect.',
                'headline' => 'Senior Product Manager at Pathao | IBA DU | Fitness enthusiast & startup mentor',
                'about' => 'I am a product strategist in Bangladesh’s thriving tech ecosystem. I thrive on solving complex consumer problems, building innovative tech, and mentoring young founders. Beyond work, I am passionate about physical fitness (weight training and 5K running), reading books on philosophy and leadership, and finding hidden food gems in old and new Dhaka. I come from a loving, close-knit family that emphasizes honesty, humility, and warmth. I am looking for an educated, positive, and empathetic life partner with a bright outlook on life, who believes in growing together as best friends and partners.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Fitness & 5K Running', 'Startup Mentoring', 'Tech Reading', 'Culinary Walks'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni',
                    'prayer_habit' => 'Regular (prays 5 times & Jummah)',
                    'beard' => 'Trimmed beard',
                ],
                'educations' => [
                    ['institution' => 'Rajuk Uttara Model College', 'level' => 'hsc', 'subject' => 'Commerce', 'year' => 2016],
                    ['institution' => 'North South University', 'level' => 'bachelor', 'subject' => 'BBA', 'year' => 2020],
                    ['institution' => 'IBA, University of Dhaka', 'level' => 'master', 'subject' => 'MBA', 'year' => 2022],
                ],
                'preference' => [
                    'age_min' => 23,
                    'age_max' => 27,
                    'height_min_cm' => 160,
                    'height_max_cm' => 172,
                    'religion' => 'islam',
                    'districts' => ['Dhaka'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'ZM',
                'color_rgb' => [55, 48, 163],
                'verification_level' => VerificationLevel::Nid->value,
            ],
            [
                'name' => 'Mahmudul Hasan Tahsin',
                'name_bn' => 'মাহমুদুল হাসান তাহসিন',
                'email' => 'mahmudul.tahsin@matrimony.test',
                'phone' => '01712000013',
                'dob' => '1994-07-19',
                'gender' => Gender::Male,
                'height_cm' => 177,
                'marital_status' => 'never_married',
                'district' => 'Sylhet',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'Shahjalal University of Science and Technology (SUST)',
                'subject' => 'BBA & MBA in International Business',
                'graduation_year' => 2019,
                'profession' => 'business',
                'job_title' => 'Managing Director & Co-Founder',
                'organization' => 'Surma Valley Organic Agro & Exports Ltd.',
                'income_min' => 320000,
                'income_max' => 550000,
                'work_location' => 'Zindabazar, Sylhet',
                'father_occupation' => 'Prominent Businessman & Tea Estate Owner',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 2,
                'family_type' => 'joint',
                'family_location' => 'Shahjalal Upashahar, Sylhet',
                'family_description' => 'Prominent and benevolent business family in Sylhet with deep community roots, philanthropy, and religious heritage.',
                'headline' => 'Agro-Export Entrepreneur | SUST Alum | Grounded, nature enthusiast & world traveler',
                'about' => 'I am an agri-business entrepreneur based in beautiful Sylhet, exporting organic tea and indigenous agricultural produce to Europe and the Middle East. I value hard work, integrity, and simplicity over superficial glamor. I love exploring pristine nature trails, birdwatching in Sreemangal tea estates, reading Quranic commentaries, and spending quality evenings with my extended family. We are a prominent, well-known family in Sylhet with strong values of charity and hospitality. Seeking a kind, pious, and family-oriented lady with high educational standards, gentle temperament, and a warm heart.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Eco-tourism', 'Nature Photography', 'Quranic Studies', 'Tea Tasting'],
                    'languages' => ['Bangla', 'English', 'Sylheti'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni',
                    'prayer_habit' => 'Always (5 times in Masjid)',
                    'beard' => 'Sunnah beard',
                ],
                'educations' => [
                    ['institution' => 'MC College, Sylhet', 'level' => 'hsc', 'subject' => 'Commerce', 'year' => 2013],
                    ['institution' => 'SUST', 'level' => 'bachelor', 'subject' => 'BBA', 'year' => 2017],
                    ['institution' => 'SUST', 'level' => 'master', 'subject' => 'MBA', 'year' => 2019],
                ],
                'preference' => [
                    'age_min' => 23,
                    'age_max' => 29,
                    'height_min_cm' => 158,
                    'height_max_cm' => 170,
                    'religion' => 'islam',
                    'districts' => ['Sylhet', 'Dhaka'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'MT',
                'color_rgb' => [31, 41, 55],
                'verification_level' => VerificationLevel::Manual->value,
                'plan' => 'premium',
            ],
            [
                'name' => 'Shafatul Islam',
                'name_bn' => 'শাফায়াতুল ইসলাম',
                'email' => 'shafatul.islam@matrimony.test',
                'phone' => '01712000014',
                'dob' => '1999-10-10',
                'gender' => Gender::Male,
                'height_cm' => 176,
                'marital_status' => 'never_married',
                'district' => 'Rajshahi',
                'religion' => 'islam',
                'education_level' => 'master',
                'institution' => 'University of Rajshahi & ICAB',
                'subject' => 'Accounting & Information Systems (ACA)',
                'graduation_year' => 2023,
                'profession' => 'accountant',
                'job_title' => 'Senior Assurance Manager (Chartered Accountant)',
                'organization' => 'PwC / A. Qasem & Co. Chartered Accountants',
                'income_min' => 165000,
                'income_max' => 245000,
                'work_location' => 'Gulshan 2, Dhaka',
                'father_occupation' => 'College Professor of Mathematics (Retired)',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Kazihata, Rajshahi (Dhaka residence: Lalmatia)',
                'family_description' => 'Honest, academic family from Rajshahi city known for intellectual integrity and warmth.',
                'headline' => 'Chartered Accountant (ACA) | RU Alum | Analytical mind, fitness & chess enthusiast',
                'about' => 'I am a qualified Chartered Accountant who loves financial strategy, corporate governance, and self-improvement. Born in the serene mango-capital Rajshahi and currently building my career in Dhaka. I consider myself grounded, disciplined, and very family-centric. On weekends, I enjoy playing competitive chess, jogging at Hatirjheel, reading business biographies, and visiting my parents in Rajshahi. I look forward to meeting an educated, graceful, and sensible life partner with mutual respect, shared spiritual grounding, and a cheerful outlook.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Chess', 'Distance Running', 'Personal Finance', 'History'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni',
                    'prayer_habit' => 'Regular (prays 5 times)',
                    'beard' => 'Trimmed beard',
                ],
                'educations' => [
                    ['institution' => 'Rajshahi College', 'level' => 'hsc', 'subject' => 'Commerce', 'year' => 2017],
                    ['institution' => 'University of Rajshahi', 'level' => 'bachelor', 'subject' => 'AIS', 'year' => 2021],
                    ['institution' => 'ICAB (Institute of Chartered Accountants)', 'level' => 'master', 'subject' => 'Chartered Accountancy (ACA)', 'year' => 2023],
                ],
                'preference' => [
                    'age_min' => 22,
                    'age_max' => 27,
                    'height_min_cm' => 156,
                    'height_max_cm' => 168,
                    'religion' => 'islam',
                    'districts' => ['Rajshahi', 'Dhaka', 'Bogura'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'SI',
                'color_rgb' => [14, 116, 144],
                'verification_level' => VerificationLevel::Selfie->value,
            ],
            [
                'name' => 'Anirban Roy',
                'name_bn' => 'অনির্বাণ রায়',
                'email' => 'anirban.roy@matrimony.test',
                'phone' => '01712000015',
                'dob' => '1997-04-05',
                'gender' => Gender::Male,
                'height_cm' => 175,
                'marital_status' => 'never_married',
                'district' => 'Dhaka',
                'religion' => 'hindu',
                'education_level' => 'bachelor',
                'institution' => 'BUET',
                'subject' => 'Electrical & Electronic Engineering (EEE)',
                'graduation_year' => 2020,
                'profession' => 'software_developer',
                'job_title' => 'Lead AI Engineer & Data Scientist',
                'organization' => 'Standard Chartered Global Business Services',
                'income_min' => 210000,
                'income_max' => 330000,
                'work_location' => 'Gulshan 2, Dhaka',
                'father_occupation' => 'Senior Deputy Director, Biman Bangladesh (Retired)',
                'mother_occupation' => 'Retired High School Headmistress',
                'siblings_count' => 1,
                'family_type' => 'nuclear',
                'family_location' => 'Shantinagar, Dhaka',
                'family_description' => 'Vibrant, open-minded Hindu family in Shantinagar with strong cultural traditions and professional heritage.',
                'headline' => 'Lead AI Scientist at Standard Chartered | BUET EEE | Acoustic music, cooking & trekking',
                'about' => 'I am a technology enthusiast working in machine learning and predictive finance. Graduating from BUET taught me analytical thinking and problem-solving. In personal life, I value sincerity, emotional maturity, companionship, and laughter. I play acoustic guitar, love cooking traditional and continental dinners on weekends, and head to the hills whenever I get vacation. Seeking an educated, progressive, and warm partner with high values, who appreciates both modern aspirations and cultural traditions.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'omnivore',
                    'hobbies' => ['Acoustic Guitar', 'Gourmet Cooking', 'Trekking', 'Astronomy'],
                    'languages' => ['Bangla', 'English', 'Hindi'],
                ],
                'religion_attrs' => [
                    'caste' => 'Kayastha',
                    'gotra' => 'Kashyapa',
                ],
                'educations' => [
                    ['institution' => 'Notre Dame College', 'level' => 'hsc', 'subject' => 'Science', 'year' => 2015],
                    ['institution' => 'BUET', 'level' => 'bachelor', 'subject' => 'EEE', 'year' => 2020],
                ],
                'preference' => [
                    'age_min' => 24,
                    'age_max' => 29,
                    'height_min_cm' => 157,
                    'height_max_cm' => 168,
                    'religion' => 'hindu',
                    'districts' => ['Dhaka'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'AR',
                'color_rgb' => [120, 53, 15],
                'verification_level' => VerificationLevel::Nid->value,
            ],
            [
                'name' => 'Saif Chowdhury',
                'name_bn' => 'সাইফ চৌধুরী',
                'email' => 'saif.chowdhury@matrimony.test',
                'phone' => '01712000016',
                'dob' => '1993-06-12',
                'gender' => Gender::Male,
                'height_cm' => 181,
                'marital_status' => 'never_married',
                'district' => 'Khulna',
                'religion' => 'islam',
                'education_level' => 'bachelor',
                'institution' => 'Bangladesh Marine Academy & WMU Sweden',
                'subject' => 'BSc in Marine Engineering',
                'graduation_year' => 2016,
                'profession' => 'engineer',
                'job_title' => 'Chief Marine Engineering Officer',
                'organization' => 'Bernhard Schulte Shipmanagement (BSSM)',
                'income_min' => 380000,
                'income_max' => 620000,
                'work_location' => 'International Commercial Shipping (Home base: Khulna)',
                'father_occupation' => 'Merchant Marine Captain (Late)',
                'mother_occupation' => 'Homemaker',
                'siblings_count' => 2,
                'family_type' => 'nuclear',
                'family_location' => 'Boyra, Khulna',
                'family_description' => 'Honorable seafaring family in Khulna known for discipline, charity, and strong moral values.',
                'headline' => 'Chief Marine Engineer | Marine Academy Alum | World traveler, disciplined & calm soul',
                'about' => 'I am a Chief Engineer in international commercial shipping, navigating global routes and leading engineering operations. My career has taught me immense discipline, patience, and appreciation for life’s simple joys. When on home rotation, I spend time with my mother and siblings in Khulna, practice landscape photography, read philosophy, and work on our family orchard. I value traditional values, honest communication, and unwavering dedication to family. Looking for an educated, calm, and understanding life partner who is ready to build a warm, supportive, and enduring life together.',
                'lifestyle' => [
                    'smoking' => 'never',
                    'drinking' => 'never',
                    'diet' => 'halal',
                    'hobbies' => ['Landscape Photography', 'Maritime History', 'Gardening', 'Swimming'],
                    'languages' => ['Bangla', 'English'],
                ],
                'religion_attrs' => [
                    'maslak' => 'Sunni',
                    'prayer_habit' => 'Regular',
                    'beard' => 'Trimmed beard',
                ],
                'educations' => [
                    ['institution' => 'Khulna Zilla School & College', 'level' => 'hsc', 'subject' => 'Science', 'year' => 2011],
                    ['institution' => 'Bangladesh Marine Academy', 'level' => 'bachelor', 'subject' => 'Marine Engineering', 'year' => 2016],
                ],
                'preference' => [
                    'age_min' => 23,
                    'age_max' => 30,
                    'height_min_cm' => 158,
                    'height_max_cm' => 170,
                    'religion' => 'islam',
                    'districts' => ['Khulna', 'Dhaka', 'Jessore'],
                    'weights' => [
                        'religion' => Importance::DealBreaker,
                        'marital_status' => Importance::DealBreaker,
                    ],
                ],
                'initials' => 'SC',
                'color_rgb' => [76, 29, 149],
                'verification_level' => VerificationLevel::Selfie->value,
                'plan' => 'premium',
            ],
        ];
    }
}
