import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'custom_color.dart';
import 'dimensions.dart';

class CustomStyle {
//------------------------dark--------------------------------
  static var darkHeading1TextStyle = GoogleFonts.inter(
    color: CustomColor.whiteColor,
    fontSize: Dimensions.headingTextSize1,
    fontWeight: FontWeight.w700,
  );
  static var darkHeading2TextStyle = GoogleFonts.inter(
    color: CustomColor.whiteColor,
    fontSize: Dimensions.headingTextSize2,
    fontWeight: FontWeight.w700,
  );
  static var darkHeading3TextStyle = GoogleFonts.inter(
    color: CustomColor.whiteColor,
    fontSize: Dimensions.headingTextSize3,
    fontWeight: FontWeight.w700,
  );
  static var darkHeading4TextStyle = GoogleFonts.inter(
    color: CustomColor.whiteColor.withOpacity(
      0.6,
    ),
    fontSize: Dimensions.headingTextSize4,
    fontWeight: FontWeight.w400,
  );
  static var darkHeading5TextStyle = GoogleFonts.inter(
    color: CustomColor.whiteColor,
    fontSize: Dimensions.headingTextSize5,
    fontWeight: FontWeight.w400,
  );

//------------------------light--------------------------------
  static var lightHeading1TextStyle = GoogleFonts.inter(
    color: CustomColor.primaryLightTextColor,
    fontSize: Dimensions.headingTextSize1,
    fontWeight: FontWeight.w700,
  );
  static var lightHeading2TextStyle = GoogleFonts.inter(
    color: CustomColor.primaryLightTextColor,
    fontSize: Dimensions.headingTextSize2,
    fontWeight: FontWeight.w700,
  );
  static var lightHeading3TextStyle = GoogleFonts.inter(
    color: CustomColor.primaryTextColor,
    fontSize: Dimensions.headingTextSize3,
    fontWeight: FontWeight.w700,
  );
  static var lightHeading4TextStyle = GoogleFonts.inter(
    color: CustomColor.primaryLightTextColor,
    fontSize: Dimensions.headingTextSize4,
    fontWeight: FontWeight.w400,
  );
  static var lightHeading5TextStyle = GoogleFonts.inter(
    color: CustomColor.primaryLightTextColor,
    fontSize: Dimensions.headingTextSize5,
    fontWeight: FontWeight.w400,
  );

  static var screenGradientBG2 = BoxDecoration(
      gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
        CustomColor.primaryDarkColor,
        CustomColor.primaryBGDarkColor,
      ]));

  static var onboardTitleStyle = GoogleFonts.inter(
      textStyle: TextStyle(
    color: CustomColor.primaryTextColor,
    fontSize: Dimensions.headingTextSize2,
    fontWeight: FontWeight.w900,
  ));

  static var onboardSubTitleStyle = GoogleFonts.inter(
      textStyle: TextStyle(
    color: CustomColor.primaryTextColor.withOpacity(0.6),
    fontSize: Dimensions.headingTextSize4 * 0.9,
    fontWeight: FontWeight.w400,
  ));

  static var onboardSkipStyle = GoogleFonts.inter(
      textStyle: TextStyle(
    color: CustomColor.primaryTextColor,
    fontSize: Dimensions.headingTextSize5,
    fontWeight: FontWeight.w500,
  ));
  static var signInInfoTitleStyle = GoogleFonts.inter(
      textStyle: TextStyle(
    color: CustomColor.primaryTextColor,
    fontSize: Dimensions.headingTextSize2,
    fontWeight: FontWeight.w700,
  ));
  static var signInInfoSubTitleStyle = GoogleFonts.inter(
      textStyle: TextStyle(
    color: CustomColor.primaryTextColor,
    fontSize: Dimensions.headingTextSize4,
    fontWeight: FontWeight.w400,
  ));
  static var f20w600pri = GoogleFonts.inter(
      textStyle: GoogleFonts.inter(
    color: CustomColor.primaryTextColor,
    fontSize: Dimensions.headingTextSize2,
    fontWeight: FontWeight.w600,
  ));
  static var labelTextStyle = GoogleFonts.inter(
      textStyle: GoogleFonts.inter(
    fontWeight: FontWeight.w600,
    color: CustomColor.primaryLightColor,
    fontSize: Dimensions.headingTextSize4,
  ));

  static var whiteTextStyle = TextStyle(
    color: CustomColor.whiteColor,
    fontSize: Dimensions.headingTextSize3,
    fontWeight: FontWeight.w500,
  );

  static var statusTextStyle = TextStyle(
    fontSize: Dimensions.headingTextSize6,
    fontWeight: FontWeight.w600,
  );

  static var yellowTextStyle = TextStyle(
    color: CustomColor.yellowColor,
    fontSize: Dimensions.headingTextSize6,
    fontWeight: FontWeight.w600,
  );
}
