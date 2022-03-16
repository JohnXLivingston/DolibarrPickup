#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/makepack-Pickup.pl
# \brief        Package builder
# \author       (c)2021-2022		John Livingston		<license@john-livingston.fr>
# \contributor  (c)2005-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
# \contributor  (c)2017 Nicolas ZABOURI <info@inovea-conseil.com>
#----------------------------------------------------------------------------

# To use: you have to be in the module root folder, and call:
# - perl build/makepack-Pickup.pl => makes the module-xxx.zip package file
# - perl build/makepack-Pickup.pl --target=zip => same as above
# - perl build/makepack-Pickup.pl --target=install => install the module in /var/www/dolibarr/htdocs/custom/xxx as user www-data.
#     You can change directory and user with options --install-dir, --install-user --install-group.
#     NB: --install-dir should not contain module name (ie should be like: /var/www/dolibarr/htdocs/custom)
#     NB: --install-dir should not contain any space.
#     NB: no quote or double quote allowed for parameters.
#     Needs the current user to be a sudoer.

$| = 1; # autoflush

use strict;
use warnings;

use Cwd;

my %REQUIREMENTTARGET=(    # Tool requirement for each package
  "TGZ"=>"tar",
  "ZIP"=>"7z"
);
my %ALTERNATEPATH=(
);

#------------------------------------------------------------------------------
# MAIN
#------------------------------------------------------------------------------
my $DIR;
my $PROG;
my $Extension;
($DIR=$0) =~ s/([^\/\\]+)$//; ($PROG=$1) =~ s/\.([^\.]*)$//; $Extension=$1;
$DIR||='.'; $DIR =~ s/([^\/\\])[\\\/]+$/$1/;

# Expected: $DIR="build", $PROG="makepack-Pickup", $Extension="pl"
if ($DIR ne 'build') {
  print "$PROG.$Extension should be called from the module root with the command: perl build/$0\n";
	print "$PROG.$Extension aborted.\n";
  sleep 2;
  exit 1;
}

my $PROJECTINPUT;
if ($PROG =~ /^makepack-(\w+)$/) {
  $PROJECTINPUT = $1;
} else {
  print "Can't find the module name in $PROG.$Extension filename.\n";
	print "$PROG.$Extension aborted.\n";
  sleep 2;
  exit 1;
}

# Detect OS type
# --------------
my $OS;
my $CR;
if ("$^O" =~ /linux/i || (-d "/etc" && -d "/var" && "$^O" !~ /cygwin/i)) { $OS='linux'; $CR=''; }
elsif (-d "/etc" && -d "/Users") { $OS='macosx'; $CR=''; }
elsif ("$^O" =~ /cygwin/i || "$^O" =~ /win32/i) { $OS='windows'; $CR="\r"; }
if (! $OS) {
  print "$PROG.$Extension was not able to detect your OS.\n";
	print "Can't continue.\n";
	print "$PROG.$Extension aborted.\n";
  sleep 2;
	exit 1;
}

# Define buildroot
# ----------------
my $TEMP;
my $PROGPATH;
if ($OS =~ /linux/) {
  $TEMP=$ENV{"TEMP"}||$ENV{"TMP"}||"/tmp";
}
if ($OS =~ /macos/) {
  $TEMP=$ENV{"TEMP"}||$ENV{"TMP"}||"/tmp";
}
if ($OS =~ /windows/) {
  $TEMP=$ENV{"TEMP"}||$ENV{"TMP"}||"c:/temp";
  $PROGPATH=$ENV{"ProgramFiles"};
}
if (! $TEMP || ! -d $TEMP) {
  print "Error: A temporary directory can not be find.\n";
  print "Check that TEMP or TMP environment variable is set correctly.\n";
	print "$PROG.$Extension aborted.\n";
  sleep 2;
  exit 2;
}
my $BUILDROOT="$TEMP/dolibarr-buildroot";


my $copyalreadydone=0;
my $batch=0;
my $ret;
my $INSTALL_USER = 'www-data';
my $INSTALL_GROUP = 'www-data';
my $INSTALL_DIR = '/var/www/dolibarr/htdocs/custom';

# Choose package targets
#-----------------------
my %CHOOSEDTARGET;
for (0..@ARGV-1) {
	if ($ARGV[$_] =~ /^-*target=(\w+)/i) {
    my $currentTarget = uc($1);
    $CHOOSEDTARGET{$currentTarget} = 1;
    $batch = 1;
  } elsif ($ARGV[$_] =~ /^-*install-user=([\w-]+)/i) {
    $INSTALL_USER = $1;
  } elsif ($ARGV[$_] =~ /^-*install-group=([\w-]+)/i) {
    $INSTALL_GROUP = $1;
  } elsif ($ARGV[$_] =~ /^-*install-dir=(.+)(\s|$)/i) {
    $INSTALL_DIR = $1;
  } else {
    die "There is an unknown parameter: '$ARGV[$_]'.\n"
  }
}

if ($CHOOSEDTARGET{'INSTALL'}) {
  if ($OS ne 'linux') {
    die "Installation script is only available for linux.\n"
  }
  if (!$INSTALL_DIR) {
    die "Invalid install directory: '$INSTALL_DIR'.\n";
  }
  if (! -d $INSTALL_DIR) {
    die "Install directory does not exist: '$INSTALL_DIR'.\n"
  }
  if (!$INSTALL_USER) {
    die "Missing --install-user.\n";
  }

  $ret = `sudo true`;
  if ($? != 0) { die "Failed to act as root. You must have root rights to install.\n"; }

  if ($INSTALL_USER) {
    if (!$INSTALL_GROUP) {
      $INSTALL_GROUP = $INSTALL_USER;
    }
    $ret = `sudo -u $INSTALL_USER true`;
    if ($? != 0) { die "Failed to act as user $INSTALL_USER.\n"; }
  }
  if ($INSTALL_GROUP) {
    $ret = `sudo -u $INSTALL_USER -g $INSTALL_GROUP true`;
    if ($? != 0) { die "Failed to act as user $INSTALL_USER.\n"; }
  }

  if ($INSTALL_DIR ne '/var/www/dolibarr/htdocs/custom') {
    if (!(-d "$INSTALL_DIR/" . lc($PROJECTINPUT))) {
      print "Please confirm that you want to install in $INSTALL_DIR/ ($INSTALL_DIR/".lc($PROJECTINPUT).") by typing 'yes'\n";
      my $input = <STDIN>;
      chomp($input);
      if ($input ne 'yes') {
        die "Aborting...\n";
      }
    }
  }

}

if (!%CHOOSEDTARGET) {
  $CHOOSEDTARGET{'ZIP'} = 1;
}

print "Move to the build directory: '".$DIR."'.\n";
chdir($DIR);

my $SOURCE="..";
my $DESTI="$SOURCE/build";

print "Makepack...\n";
print "Module name: $PROJECTINPUT\n";
print "Current directory: ".getcwd()."\n";
print "Source directory: $SOURCE\n";
print "Target directory: $DESTI\n";


my @PROJECTLIST=();
@PROJECTLIST=($PROJECTINPUT);

# Loop on each projects
foreach my $PROJECT (@PROJECTLIST) {

	my $PROJECTLC=lc($PROJECT);
  my $BUILDPROJECTDIR = "$BUILDROOT/$PROJECTLC/htdocs/$PROJECTLC";

	if (! -f "makepack-".$PROJECT.".conf")
	{
	  print "Error: can't open conf file makepack-".$PROJECT.".conf\n";
		print "$PROG.$Extension aborted.\n";
	  sleep 2;
	  exit 2;
	}

	# Get version $MAJOR, $MINOR and $BUILD
	print "Version detected for module ".$PROJECT.": ";
  my $modFilePath = "$SOURCE/core/modules/mod".$PROJECT.".class.php";
	my $result=open(IN,"<".$modFilePath);
	if (! $result) {
    die "\nError: Can't open descriptor file $modFilePath for reading.\n";
  }
  my $PROJVERSION = '';
  while(<IN>) {
    if ($_ =~ /this->version\s*=\s*'([\d\.]+)'/) { $PROJVERSION=$1; last; }
  }
  close IN;
  if ($PROJVERSION !~ /^\d+\.\d+(\.\d+)?$/) {
    print "Invalid project version number: '$PROJVERSION'.\n";
    print "Aborting...";
    sleep(2);
    exit 1;
  }
	print "Project version: ".$PROJVERSION."\n";

	my ($MAJOR,$MINOR,$BUILD)=split(/\./,$PROJVERSION,3);

	my $FILENAME="$PROJECTLC";
	my $ARCHIVEFILENAME="module_$PROJECTLC-$MAJOR.$MINOR".($BUILD ne '' ? ".$BUILD" : "");

	# Test if requirement is ok
	#--------------------------
	foreach my $target (keys %CHOOSEDTARGET) {
    if ($target eq 'INSTALL') {
      next;
    }
    foreach my $req (split(/[,\s]/,$REQUIREMENTTARGET{$target})) {
      # Test
      print "Test requirement for target $target: Search '$req'... ";
      $ret=`"$req" 2>&1`;
      my $coderetour=$?;
      my $coderetour2=$coderetour>>8;
      if ($coderetour != 0 && (($coderetour2 == 1 && $OS =~ /windows/ && $ret !~ /Usage/i) || ($coderetour2 == 127 && $OS !~ /windows/)) && $PROGPATH) {
          # Not found error, we try in PROGPATH
          $ret=`"$PROGPATH/$ALTERNATEPATH{$req}/$req\" 2>&1`;
          $coderetour=$?; $coderetour2=$coderetour>>8;
          $REQUIREMENTTARGET{$target}="$PROGPATH/$ALTERNATEPATH{$req}/$req";
      }

      if ($coderetour != 0 && (($coderetour2 == 1 && $OS =~ /windows/ && $ret !~ /Usage/i) || ($coderetour2 == 127 && $OS !~ /windows/))) {
          # Not found error
          print "Not found\nCan't build target $target. Requirement '$req' not found in PATH\n";
          $CHOOSEDTARGET{$target}=-1;
          last;
      } else {
          # Pas erreur ou erreur autre que programme absent
          print " Found ".$REQUIREMENTTARGET{$target}."\n";
      }
    }
	}

	print "\n";

  if (-f "$SOURCE/package.json") {
    print "Building npm package...\n";
    my $olddir=getcwd();
    chdir($SOURCE);

    my $npm_command = "FORCE_COLOR=true npm run build |";
    open NPM, $npm_command or die "Cant call npm run build\n";
    while (my $line = <NPM>) {
      print $line;
    }
    close NPM;
    if ($? != 0) { die "Failed to run npm build in $SOURCE.\n"; }

    chdir($olddir);
  }

  if ((-d "$SOURCE/documentation") && (-f "$SOURCE/documentation/config.toml")) {
    print "Generating hugo documentation...\n";
    my $olddir=getcwd();
    chdir($SOURCE);

    my $hugo_command = "DOLIBARRPICKUP_INLINEDOC=1 hugo -s documentation --baseURL='/custom/pickup/documentation/public/' |";
    open HUGO, $hugo_command or die "Cant call hugo to compile documentation, have you installed the hugo package?\n";
    while (my $line = <HUGO>) {
      print $line;
    }
    close HUGO;
    if ($? != 0) { die "Failed to run hugo in $SOURCE.\n"; }

    chdir($olddir);
  }

	# Check if there is at least one target to build
	#----------------------------------------------
	my $nboftargetok=0;
	my $nboftargetneedbuildroot=0;
	my $nboftargetneedcvs=0;
	foreach my $target (keys %CHOOSEDTARGET) {
	  if ($CHOOSEDTARGET{$target} < 0) { next; }
		if ($target ne 'EXE' && $target ne 'EXEDOLIWAMP') {
			$nboftargetneedbuildroot++;
		}
		if ($target eq 'SNAPSHOT') {
			$nboftargetneedcvs++;
		}
		$nboftargetok++;
	}

	if ($nboftargetok) {

    # Update CVS if required
    #-----------------------
    if ($nboftargetneedcvs) {
	    die "Not implemented."
		}

    # Update buildroot if required
    #-----------------------------
    if ($nboftargetneedbuildroot) {
      if (! $copyalreadydone) {
        print "Delete directory $BUILDROOT\n";
        $ret=`rm -fr "$BUILDROOT"`;

        print "Making dir $BUILDPROJECTDIR\n";
        $ret=`mkdir -p "$BUILDPROJECTDIR"`;
        if ($? != 0) { die "Failed to create dir $BUILDPROJECTDIR.\n"; }
        
        my $result=open(IN,"<makepack-".$PROJECT.".conf");
        if (! $result) { die "Error: Can't open conf file makepack-".$PROJECT.".conf for reading.\n"; }
        while(<IN>) {
          my $entry=$_;
          if ($entry =~ /^#/) { next; }	# Do not process comments

          $entry =~ s/\n//;

          if ($entry =~ /^!(.*)$/) {		# Exclude so remove file/dir
            print "Remove $BUILDPROJECTDIR/$1\n";
            $ret=`rm -fr "$BUILDPROJECTDIR/"$1`;
            if ($? != 0) { die "Failed to delete a file to exclude declared into makepack-".$PROJECT.".conf file (Fails on line ".$entry.")\n"; }
            next;
          }

          $entry =~ /^(.*)\/[^\/]+/;
          print "Create directory $BUILDPROJECTDIR/$1\n";
          $ret=`mkdir -p "$BUILDPROJECTDIR/$1"`;
          if ($entry !~ /version\-/) {
            print "Copy $SOURCE/$entry into $BUILDPROJECTDIR/$entry\n";
            $ret=`cp -pr "$SOURCE/$entry" "$BUILDPROJECTDIR/$entry"`;
            if ($? != 0) { die "Failed to make copy of a file declared into makepack-".$PROJECT.".conf file (Fails on line ".$entry.")\n"; }
          }
        }
        close IN;

        my @timearray=localtime(time());
        my $fulldate=($timearray[5]+1900).'-'.($timearray[4]+1).'-'.$timearray[3].' '.$timearray[2].':'.$timearray[1];
        $ret=`mkdir -p "$BUILDROOT/$PROJECTLC/build"`;
        open(VF,">$BUILDROOT/$PROJECTLC/build/version-".$PROJECTLC.".txt");

        print "Create version file $BUILDROOT/$PROJECTLC/build/version-".$PROJECTLC.".txt with date ".$fulldate."\n";
        print VF "Version: ".$MAJOR.".".$MINOR.($BUILD ne ''?".$BUILD":"")."\n";
        print VF "Build  : ".$fulldate."\n";
        close VF;
      }
      print "Clean $BUILDROOT/htdocs/\n";
      $ret=`rm -fr $BUILDPROJECTDIR/.cache`;
      $ret=`rm -fr $BUILDPROJECTDIR/.project`;
      $ret=`rm -fr $BUILDPROJECTDIR/.settings`;
      $ret=`rm -fr $BUILDPROJECTDIR/index.php`;
      $ret=`rm -fr $BUILDPROJECTDIR/build/html`;
      $ret=`rm -fr $BUILDPROJECTDIR/documents`;
      $ret=`rm -fr $BUILDPROJECTDIR/document`;
      $ret=`rm -fr $BUILDPROJECTDIR/htdocs/conf/conf.php.mysql`;
      $ret=`rm -fr $BUILDPROJECTDIR/htdocs/conf/conf.php.old`;
      $ret=`rm -fr $BUILDPROJECTDIR/htdocs/conf/conf.php.postgres`;
      $ret=`rm -fr $BUILDPROJECTDIR/htdocs/conf/conf*sav*`;
      $ret=`rm -fr $BUILDPROJECTDIR/htdocs/custom`;
      $ret=`rm -fr $BUILDPROJECTDIR/htdocs/custom2`;
      $ret=`rm -fr $BUILDPROJECTDIR/test`;
      $ret=`rm -fr $BUILDPROJECTDIR/Thumbs.db $BUILDPROJECTDIR/*/Thumbs.db $BUILDPROJECTDIR/*/*/Thumbs.db $BUILDPROJECTDIR/*/*/*/Thumbs.db $BUILDPROJECTDIR/*/*/*/*/Thumbs.db`;
      $ret=`rm -fr $BUILDPROJECTDIR/CVS* $BUILDPROJECTDIR/*/CVS* $BUILDPROJECTDIR/*/*/CVS* $BUILDPROJECTDIR/*/*/*/CVS* $BUILDPROJECTDIR/*/*/*/*/CVS* $BUILDPROJECTDIR/*/*/*/*/*/CVS*`;
		}

    # Build package for each target
    #------------------------------
    foreach my $target (keys %CHOOSEDTARGET) {
      if ($CHOOSEDTARGET{$target} < 0) { next; }

      print "\nBuild package for target $target\n";

      if ($target eq 'TGZ') {
        die "Not implemented.";
        # print "Remove target $ARCHIVEFILENAME.tgz...\n";
        # unlink("$DESTI/$ARCHIVEFILENAME.tgz");
        # print "Compress $BUILDROOT/* into $ARCHIVEFILENAME.tgz...\n";
        # $cmd="tar --exclude-vcs --exclude *.tgz --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$ARCHIVEFILENAME.tgz\" .";
        # $ret=`$cmd`;
        # if ($OS =~ /windows/i) {
        #   print "Move $ARCHIVEFILENAME.tgz to $DESTI/$ARCHIVEFILENAME.tgz\n";
        #   $ret=`mv "$ARCHIVEFILENAME.tgz" "$DESTI/$ARCHIVEFILENAME.tgz"`;
        # } else {
        #   $ret=`mv "$ARCHIVEFILENAME.tgz" "$DESTI/$ARCHIVEFILENAME.tgz"`;
        # }
        # next;
      }

      if ($target eq 'ZIP') {
        print "Remove target $ARCHIVEFILENAME.zip...\n";
        unlink "$DESTI/$ARCHIVEFILENAME.zip";
        print "Compress $ARCHIVEFILENAME into $ARCHIVEFILENAME.zip...\n";

        print "Go to directory $BUILDROOT/$PROJECTLC\n";
        my $olddir=getcwd();
        chdir("$BUILDROOT/$PROJECTLC");
        my $cmd= "7z a -r -tzip -mx $BUILDROOT/$ARCHIVEFILENAME.zip *";
        print $cmd."\n";
        $ret= `$cmd`;
        chdir("$olddir");

        print "Move $ARCHIVEFILENAME.zip to $DESTI/$ARCHIVEFILENAME.zip\n";
        $ret=`mv "$BUILDROOT/$ARCHIVEFILENAME.zip" "$DESTI/$ARCHIVEFILENAME.zip"`;
        # $ret=`chown $OWNER.$GROUP "$DESTI/$ARCHIVEFILENAME.zip"`;
        next;
      }

      if ($target eq 'INSTALL') {
        print "Installing in web directory '$INSTALL_DIR/$PROJECTLC' as user $INSTALL_USER:$INSTALL_GROUP...\n";

        print "Go to directory $BUILDROOT/$PROJECTLC/htdocs/\n";
        my $olddir=getcwd();
        chdir("$BUILDROOT/$PROJECTLC/htdocs");

        print "Deleting old files with: sudo rm -rf ".$INSTALL_DIR."/".$PROJECTLC."\n";
        $ret=`sudo rm -rf "$INSTALL_DIR/$PROJECTLC"`;
        if ($? != 0) { die "Failed to delete previous files in $INSTALL_DIR/$PROJECTLC/.\n"; }

        print "Copying files $PROJECTLC to $INSTALL_DIR/$PROJECTLC/\n";
        $ret=`sudo cp -pr "$PROJECTLC/" "$INSTALL_DIR/$PROJECTLC"`;
        if ($? != 0) { die "Failed to make copy of files to $INSTALL_DIR/$PROJECTLC/.\n"; }

        print "Chown $INSTALL_USER:$INSTALL_GROUP on $INSTALL_DIR/$PROJECTLC\n";
        $ret=`sudo chown -R $INSTALL_USER:$INSTALL_GROUP "$INSTALL_DIR/$PROJECTLC"`;
        if ($? != 0) { die "Failed to chown files in $INSTALL_DIR/$PROJECTLC/.\n"; }

        print "Chmod -w on $INSTALL_DIR/$PROJECTLC\n";
        $ret=`sudo find "$INSTALL_DIR/$PROJECTLC" -type f -exec chmod 444 {} +`;
        if ($? != 0) { die "Failed to chmod files in $INSTALL_DIR/$PROJECTLC/.\n"; }

        print "Restoring previous directory '$olddir'.\n";
        chdir("$olddir");
      }

      if ($target eq 'EXE') {
        die "Not implemented";
      }
    }
	}

	print "\n----- Summary -----\n";
	foreach my $target (keys %CHOOSEDTARGET) {
    if ($CHOOSEDTARGET{$target} < 0) {
      print "Package $target not built (bad requirement).\n";
    } elsif ($target eq 'INSTALL') {
      print "Package installed succesfully in $INSTALL_DIR\n";
    } else {
      print "Package $target built successfully in $DESTI\n";
    }
	}
}


if (! $batch) {
    print "\nPress key to finish...";
    my $WAITKEY=<STDIN>;
}

0;
