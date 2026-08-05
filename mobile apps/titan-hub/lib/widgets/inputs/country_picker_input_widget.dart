import 'package:country_code_picker/country_code_picker.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../utils/basic_screen_imports.dart';

class ProfileCountryCodePickerWidget extends StatelessWidget {
  const ProfileCountryCodePickerWidget({
    super.key,
    required this.controller,
    this.onChanged,
    this.initialSelection,
  });

  final TextEditingController controller;
  final ValueChanged? onChanged;
  final String? initialSelection;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          height: Dimensions.inputBoxHeight * 0.76,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
            border: Border.all(
              color: CustomColor.primaryLightColor.withOpacity(0.2),
            ),
          ),
          width: double.infinity,
          child: CountryCodePicker(
            textStyle: CustomStyle.darkHeading3TextStyle.copyWith(
              fontWeight: FontWeight.w700,
              color: Get.isDarkMode
                  ? CustomColor.primaryDarkTextColor
                  : CustomColor.primaryTextColor,
            ),
            enabled: true,
            padding: EdgeInsets.zero,
            onChanged: onChanged,
            dialogBackgroundColor: CustomColor.whiteColor,
            showOnlyCountryWhenClosed: true,
            showDropDownButton: true,
            initialSelection: initialSelection ?? 'US',
            showCountryOnly: true,
            alignLeft: true,
            searchStyle: CustomStyle.lightHeading4TextStyle,
            dialogTextStyle: GoogleFonts.inter(
              color: CustomColor.blackColor,
              fontSize: Dimensions.headingTextSize4,
              fontWeight: FontWeight.w400,
            ),
          ),
        ),
        SizedBox(
          height: Dimensions.heightSize,
        ),
      ],
    );
  }
}
