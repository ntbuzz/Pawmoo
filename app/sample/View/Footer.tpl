
.info-box#disp_about{about_info} => [ size => "250,110"
  h3. => [ ${'AppData.sysinfo.platform'} ]
  p. => [ ~ ${'AppData.sysinfo.platform'} is PHP wild-framwork. :-) ~ ]
  <hr>
  pre. => [ "^
      ${'AppData.sysinfo.copyright'}
      license: MIT
      version: ${'AppData.sysinfo.version'}
      System Require: PHP 5.6 or Higher
    " ]
]
