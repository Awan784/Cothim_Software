<?php

$cities = [
    'Abbottabad', 'Ahmedpur East', 'Arifwala', 'Astore', 'Attock', 'Awaran',
    'Badin', 'Bagh', 'Bahawalnagar', 'Bahawalpur', 'Bajaur', 'Bannu', 'Barkhan',
    'Battagram', 'Bela', 'Bhakkar', 'Bhalwal', 'Bhimber', 'Buner', 'Burewala',
    'Chachro', 'Chakwal', 'Chaman', 'Charsadda', 'Chilas', 'Chiniot', 'Chishtian',
    'Chitral', 'Chunian', 'Dadu', 'Daharki', 'Daska', 'Dera Allah Yar',
    'Dera Bugti', 'Dera Ghazi Khan', 'Dera Ismail Khan', 'Dera Murad Jamali',
    'Dina', 'Diplo', 'Dir', 'Duki', 'Dunyapur', 'Faisalabad', 'Farooqabad',
    'Fateh Jang', 'Fort Abbas', 'Gambat', 'Ghanche', 'Ghakhar Mandi', 'Ghotki',
    'Ghizer', 'Gilgit', 'Gojra', 'Gujar Khan', 'Gujranwala', 'Gujrat', 'Gwadar',
    'Hafizabad', 'Hala', 'Hangu', 'Harnai', 'Haripur', 'Haroonabad', 'Hasan Abdal',
    'Hasilpur', 'Hattian Bala', 'Haveli', 'Hub', 'Hunza', 'Hyderabad',
    'Isa Khel', 'Islamabad', 'Islamkot', 'Jacobabad', 'Jaffarabad', 'Jamrud',
    'Jamshoro', 'Jampur', 'Jaranwala', 'Jatoi', 'Jhang', 'Jhelum', 'Jhal Magsi',
    'Jhuddo', 'Jiwani', 'Johi', 'Kabirwala', 'Kahuta', 'Kalat', 'Kalabagh',
    'Kallar Kahar', 'Kamalia', 'Kamoke', 'Kandhkot', 'Kandiaro', 'Karachi',
    'Karak', 'Kashmore', 'Kasur', 'Kech', 'Khairpur', 'Khanewal', 'Khanpur',
    'Kharan', 'Kharian', 'Kharmang', 'Khushab', 'Khuzdar', 'Khyber', 'Kohat',
    'Kohlu', 'Kotri', 'Kot Addu', 'Kot Diji', 'Kotli', 'Kot Momin', 'Korangi',
    'Kunri', 'Kundian', 'Lahore', 'Lakki Marwat', 'Lalamusa', 'Landi Kotal',
    'Larkana', 'Lasbela', 'Layyah', 'Liaquatpur', 'Lodhran', 'Loralai',
    'Lower Dir', 'Mailsi', 'Malakand', 'Malakwal', 'Mandi Bahauddin', 'Mansehra',
    'Mardan', 'Mastung', 'Matli', 'Matiari', 'Mehar', 'Mehrabpur', 'Mian Channu',
    'Mianwali', 'Mingora', 'Minchinabad', 'Miranshah', 'Mirpur', 'Mirpur Khas',
    'Mirpur Mathelo', 'Mithi', 'Mohmand', 'Moro', 'Multan', 'Muridke', 'Murree',
    'Musakhel', 'Muzaffarabad', 'Muzaffargarh', 'Nagar', 'Nagarparkar',
    'Nankana Sahib', 'Narang Mandi', 'Narowal', 'Naushahro Feroze', 'Nawabshah',
    'Neelum', 'North Waziristan', 'Nowshera', 'Nushki', 'Okara', 'Orakzai',
    'Ormara', 'Pakpattan', 'Pano Aqil', 'Panjgur', 'Parachinar', 'Pasni',
    'Pasrur', 'Pattoki', 'Peshawar', 'Phalia', 'Pindi Bhattian', 'Pindi Gheb',
    'Pishin', 'Pithoro', 'Qambar', 'Qila Abdullah', 'Qila Saifullah', 'Quetta',
    'Rahim Yar Khan', 'Rajanpur', 'Ratodero', 'Rawalakot', 'Rawalpindi',
    'Renala Khurd', 'Rohri', 'Sadiqabad', 'Sahiwal', 'Saidu Sharif', 'Sakrand',
    'Sambrial', 'Samundri', 'Sanghar', 'Sangla Hill', 'Sarai Alamgir', 'Sargodha',
    'Sehwan', 'Shahdadkot', 'Shahdadpur', 'Shahkot', 'Shakargarh', 'Shangla',
    'Sheikhupura', 'Sherani', 'Shigar', 'Shikarpur', 'Shujaabad', 'Sialkot',
    'Sibi', 'Skardu', 'Sohbatpur', 'South Waziristan', 'Sudhanoti', 'Sujawal',
    'Sukkur', 'Swabi', 'Swat', 'Talagang', 'Tando Adam', 'Tando Allahyar',
    'Tando Muhammad Khan', 'Tank', 'Taunsa', 'Taxila', 'Thatta', 'Tharparkar',
    'Timergara', 'Toba Tek Singh', 'Torghar', 'Turbat', 'Ubauro', 'Uch Sharif',
    'Umerkot', 'Upper Dir', 'Usta Muhammad', 'Uthal', 'Vehari', 'Wah Cantonment',
    'Wana', 'Washuk', 'Wazirabad', 'Winder', 'Yazman', 'Zafarwal', 'Zhob', 'Ziarat',
];

$cities = array_values(array_unique($cities));
sort($cities, SORT_NATURAL | SORT_FLAG_CASE);

return [
    'cities' => $cities,
];
