#!/bin/bash
#This is a build script for updating translations
#    This file is part of elad-gallery.
#
#    elad-gallery is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    elad-gallery is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with elad-gallery.  If not, see <http://www.gnu.org/licenses/>.
IFS='
'
a=( $(grep "trans(\"" Gallery.php | sed -e "s/^.*trans(\"//" -e "s/\").*//" -e "/^$/d" ) )

echo '<?' > en.php
echo '$lang = array (' >> en.php
element_count=${#a[@]}
index=0

while [ "$index" -lt "$element_count" ]
do   
  echo "	'"${a[$index]}"' => ''," >> en.php
  let "index = $index + 1"
done
echo ');' >>en.php
echo "?>" >>en.php
mv en.php template.php
#TODO: Get gettext conversion working
#php2po en.php
#mv en.php po/template.php
#mv messages.pot po/
#cd po/
#for LANG in $(cat LINGUAS)
#do
#	msgmerge -U --previous $LANG.po messages.pot
#done
