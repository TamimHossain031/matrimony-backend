<?php

namespace Database\Seeders;

/**
 * district name => upazilas. District names must match LookupSeeder exactly.
 */
class UpazilaData
{
    public static function all(): array
    {
        return [
            // Dhaka division
            'Dhaka' => ['Dhamrai', 'Dohar', 'Keraniganj', 'Nawabganj', 'Savar'],
            'Gazipur' => ['Gazipur Sadar', 'Kaliakair', 'Kaliganj', 'Kapasia', 'Sreepur'],
            'Narayanganj' => ['Araihazar', 'Bandar', 'Narayanganj Sadar', 'Rupganj', 'Sonargaon'],
            'Tangail' => ['Basail', 'Bhuapur', 'Delduar', 'Dhanbari', 'Ghatail', 'Gopalpur', 'Kalihati', 'Madhupur', 'Mirzapur', 'Nagarpur', 'Sakhipur', 'Tangail Sadar'],
            'Narsingdi' => ['Belabo', 'Monohardi', 'Narsingdi Sadar', 'Palash', 'Raipura', 'Shibpur'],
            'Kishoreganj' => ['Austagram', 'Bajitpur', 'Bhairab', 'Hossainpur', 'Itna', 'Karimganj', 'Katiadi', 'Kishoreganj Sadar', 'Kuliarchar', 'Mithamain', 'Nikli', 'Pakundia', 'Tarail'],
            'Manikganj' => ['Daulatpur', 'Ghior', 'Harirampur', 'Manikganj Sadar', 'Saturia', 'Shivalaya', 'Singair'],
            'Munshiganj' => ['Gazaria', 'Lohajang', 'Munshiganj Sadar', 'Sirajdikhan', 'Sreenagar', 'Tongibari'],
            'Rajbari' => ['Baliakandi', 'Goalanda', 'Kalukhali', 'Pangsha', 'Rajbari Sadar'],
            'Madaripur' => ['Dasar', 'Kalkini', 'Madaripur Sadar', 'Rajoir', 'Shibchar'],
            'Gopalganj' => ['Gopalganj Sadar', 'Kashiani', 'Kotalipara', 'Muksudpur', 'Tungipara'],
            'Faridpur' => ['Alfadanga', 'Bhanga', 'Boalmari', 'Charbhadrasan', 'Faridpur Sadar', 'Madhukhali', 'Nagarkanda', 'Sadarpur', 'Saltha'],
            'Shariatpur' => ['Bhedarganj', 'Damudya', 'Gosairhat', 'Naria', 'Shariatpur Sadar', 'Zajira'],

            // Chattogram division
            'Chattogram' => ['Anwara', 'Banshkhali', 'Boalkhali', 'Chandanaish', 'Fatikchhari', 'Hathazari', 'Karnaphuli', 'Lohagara', 'Mirsharai', 'Patiya', 'Rangunia', 'Raozan', 'Sandwip', 'Satkania', 'Sitakunda'],
            "Cox's Bazar" => ['Chakaria', "Cox's Bazar Sadar", 'Kutubdia', 'Maheshkhali', 'Pekua', 'Ramu', 'Teknaf', 'Ukhia'],
            'Cumilla' => ['Barura', 'Brahmanpara', 'Burichang', 'Chandina', 'Chauddagram', 'Cumilla Adarsha Sadar', 'Cumilla Sadar Dakshin', 'Daudkandi', 'Debidwar', 'Homna', 'Laksam', 'Lalmai', 'Meghna', 'Monohargonj', 'Muradnagar', 'Nangalkot', 'Titas'],
            'Feni' => ['Chhagalnaiya', 'Daganbhuiyan', 'Feni Sadar', 'Fulgazi', 'Parshuram', 'Sonagazi'],
            'Brahmanbaria' => ['Akhaura', 'Ashuganj', 'Bancharampur', 'Bijoynagar', 'Brahmanbaria Sadar', 'Kasba', 'Nabinagar', 'Nasirnagar', 'Sarail'],
            'Rangamati' => ['Baghaichhari', 'Barkal', 'Belaichhari', 'Juraichhari', 'Kaptai', 'Kawkhali', 'Langadu', 'Naniarchar', 'Rajasthali', 'Rangamati Sadar'],
            'Noakhali' => ['Begumganj', 'Chatkhil', 'Companiganj', 'Hatiya', 'Kabirhat', 'Noakhali Sadar', 'Senbagh', 'Sonaimuri', 'Subarnachar'],
            'Chandpur' => ['Chandpur Sadar', 'Faridganj', 'Haimchar', 'Haziganj', 'Kachua', 'Matlab Dakshin', 'Matlab Uttar', 'Shahrasti'],
            'Lakshmipur' => ['Kamalnagar', 'Lakshmipur Sadar', 'Raipur', 'Ramganj', 'Ramgati'],
            'Khagrachhari' => ['Dighinala', 'Guimara', 'Khagrachhari Sadar', 'Lakshmichhari', 'Mahalchhari', 'Manikchhari', 'Matiranga', 'Panchhari', 'Ramgarh'],
            'Bandarban' => ['Alikadam', 'Bandarban Sadar', 'Lama', 'Naikhongchhari', 'Rowangchhari', 'Ruma', 'Thanchi'],

            // Rajshahi division
            'Rajshahi' => ['Bagha', 'Bagmara', 'Charghat', 'Durgapur', 'Godagari', 'Mohanpur', 'Paba', 'Puthia', 'Tanore'],
            'Natore' => ['Bagatipara', 'Baraigram', 'Gurudaspur', 'Lalpur', 'Natore Sadar', 'Singra'],
            'Naogaon' => ['Atrai', 'Badalgachhi', 'Dhamoirhat', 'Manda', 'Mohadevpur', 'Naogaon Sadar', 'Niamatpur', 'Patnitala', 'Porsha', 'Raninagar', 'Sapahar'],
            'Chapai Nawabganj' => ['Bholahat', 'Gomastapur', 'Nachole', 'Nawabganj Sadar', 'Shibganj'],
            'Pabna' => ['Atgharia', 'Bera', 'Bhangura', 'Chatmohar', 'Faridpur', 'Ishwardi', 'Pabna Sadar', 'Santhia', 'Sujanagar'],
            'Bogura' => ['Adamdighi', 'Bogura Sadar', 'Dhunat', 'Dhupchanchia', 'Gabtali', 'Kahaloo', 'Nandigram', 'Sariakandi', 'Shajahanpur', 'Sherpur', 'Shibganj', 'Sonatola'],
            'Sirajganj' => ['Belkuchi', 'Chauhali', 'Kamarkhanda', 'Kazipur', 'Raiganj', 'Shahjadpur', 'Sirajganj Sadar', 'Tarash', 'Ullahpara'],
            'Joypurhat' => ['Akkelpur', 'Joypurhat Sadar', 'Kalai', 'Khetlal', 'Panchbibi'],

            // Khulna division
            'Khulna' => ['Batiaghata', 'Dacope', 'Dumuria', 'Dighalia', 'Koyra', 'Paikgachha', 'Phultala', 'Rupsa', 'Terokhada'],
            'Bagerhat' => ['Bagerhat Sadar', 'Chitalmari', 'Fakirhat', 'Kachua', 'Mollahat', 'Mongla', 'Morrelganj', 'Rampal', 'Sarankhola'],
            'Satkhira' => ['Assasuni', 'Debhata', 'Kalaroa', 'Kaliganj', 'Satkhira Sadar', 'Shyamnagar', 'Tala'],
            'Jessore' => ['Abhaynagar', 'Bagherpara', 'Chaugachha', 'Jhikargachha', 'Keshabpur', 'Jessore Sadar', 'Manirampur', 'Sharsha'],
            'Magura' => ['Magura Sadar', 'Mohammadpur', 'Shalikha', 'Sreepur'],
            'Jhenaidah' => ['Harinakunda', 'Jhenaidah Sadar', 'Kaliganj', 'Kotchandpur', 'Maheshpur', 'Shailkupa'],
            'Narail' => ['Kalia', 'Lohagara', 'Narail Sadar'],
            'Kushtia' => ['Bheramara', 'Daulatpur', 'Khoksa', 'Kumarkhali', 'Kushtia Sadar', 'Mirpur'],
            'Chuadanga' => ['Alamdanga', 'Chuadanga Sadar', 'Damurhuda', 'Jibannagar'],
            'Meherpur' => ['Gangni', 'Meherpur Sadar', 'Mujibnagar'],

            // Barishal division
            'Barishal' => ['Agailjhara', 'Babuganj', 'Bakerganj', 'Banaripara', 'Barishal Sadar', 'Gournadi', 'Hizla', 'Mehendiganj', 'Muladi', 'Wazirpur'],
            'Bhola' => ['Bhola Sadar', 'Burhanuddin', 'Char Fasson', 'Daulatkhan', 'Lalmohan', 'Manpura', 'Tazumuddin'],
            'Patuakhali' => ['Bauphal', 'Dashmina', 'Dumki', 'Galachipa', 'Kalapara', 'Mirzaganj', 'Patuakhali Sadar', 'Rangabali'],
            'Pirojpur' => ['Bhandaria', 'Kawkhali', 'Mathbaria', 'Nazirpur', 'Nesarabad', 'Pirojpur Sadar', 'Zianagar'],
            'Barguna' => ['Amtali', 'Bamna', 'Barguna Sadar', 'Betagi', 'Patharghata', 'Taltali'],
            'Jhalokati' => ['Jhalokati Sadar', 'Kathalia', 'Nalchity', 'Rajapur'],

            // Sylhet division
            'Sylhet' => ['Balaganj', 'Beanibazar', 'Bishwanath', 'Companiganj', 'Dakshin Surma', 'Fenchuganj', 'Golapganj', 'Gowainghat', 'Jaintiapur', 'Kanaighat', 'Osmaninagar', 'Sylhet Sadar', 'Zakiganj'],
            'Moulvibazar' => ['Barlekha', 'Juri', 'Kamalganj', 'Kulaura', 'Moulvibazar Sadar', 'Rajnagar', 'Sreemangal'],
            'Habiganj' => ['Ajmiriganj', 'Bahubal', 'Baniachong', 'Chunarughat', 'Habiganj Sadar', 'Lakhai', 'Madhabpur', 'Nabiganj', 'Shayestaganj'],
            'Sunamganj' => ['Bishwamvarpur', 'Chhatak', 'Dakshin Sunamganj', 'Derai', 'Dharamapasha', 'Dowarabazar', 'Jagannathpur', 'Jamalganj', 'Sullah', 'Sunamganj Sadar', 'Tahirpur'],

            // Rangpur division
            'Rangpur' => ['Badarganj', 'Gangachara', 'Kaunia', 'Mithapukur', 'Pirgachha', 'Pirganj', 'Rangpur Sadar', 'Taraganj'],
            'Dinajpur' => ['Birampur', 'Birganj', 'Biral', 'Bochaganj', 'Chirirbandar', 'Dinajpur Sadar', 'Ghoraghat', 'Hakimpur', 'Kaharole', 'Khansama', 'Nawabganj', 'Parbatipur', 'Phulbari'],
            'Kurigram' => ['Bhurungamari', 'Char Rajibpur', 'Chilmari', 'Kurigram Sadar', 'Nageshwari', 'Phulbari', 'Rajarhat', 'Raomari', 'Ulipur'],
            'Gaibandha' => ['Fulchhari', 'Gaibandha Sadar', 'Gobindaganj', 'Palashbari', 'Sadullapur', 'Saghata', 'Sundarganj'],
            'Nilphamari' => ['Dimla', 'Domar', 'Jaldhaka', 'Kishoreganj', 'Nilphamari Sadar', 'Saidpur'],
            'Panchagarh' => ['Atwari', 'Boda', 'Debiganj', 'Panchagarh Sadar', 'Tetulia'],
            'Thakurgaon' => ['Baliadangi', 'Haripur', 'Pirganj', 'Ranisankail', 'Thakurgaon Sadar'],
            'Lalmonirhat' => ['Aditmari', 'Hatibandha', 'Kaliganj', 'Lalmonirhat Sadar', 'Patgram'],

            // Mymensingh division
            'Mymensingh' => ['Bhaluka', 'Dhobaura', 'Fulbaria', 'Gaffargaon', 'Gauripur', 'Haluaghat', 'Ishwarganj', 'Muktagachha', 'Mymensingh Sadar', 'Nandail', 'Phulpur', 'Tarakanda', 'Trishal'],
            'Jamalpur' => ['Bakshiganj', 'Dewanganj', 'Islampur', 'Jamalpur Sadar', 'Madarganj', 'Melandaha', 'Sarishabari'],
            'Netrokona' => ['Atpara', 'Barhatta', 'Durgapur', 'Kalmakanda', 'Kendua', 'Khaliajuri', 'Madan', 'Mohanganj', 'Netrokona Sadar', 'Purbadhala'],
            'Sherpur' => ['Jhenaigati', 'Nakla', 'Nalitabari', 'Sherpur Sadar', 'Sreebardi'],
        ];
    }
}
