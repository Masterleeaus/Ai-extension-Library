import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../backend/model/recipient_models/recipient_save_info_model.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';

class ReceiverCountryDropDown extends StatelessWidget {
  final RxString selectMethod;
  final List<ReceiverCountry> itemsList;
  final void Function(ReceiverCountry?)? onChanged;

  const ReceiverCountryDropDown({
    required this.itemsList,
    super.key,
    required this.selectMethod,
    this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Obx(() => Container(
          height: Dimensions.inputBoxHeight * 0.75,
          decoration: BoxDecoration(
            border: Border.all(
              color: CustomColor.primaryLightColor,
              width: 2,
            ),
            borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
          ),
          child: DropdownButtonHideUnderline(
            child: Padding(
              padding: const EdgeInsets.only(left: 5, right: 20),
              child: DropdownButton(
                dropdownColor: CustomColor.whiteColor,
                hint: Padding(
                  padding: EdgeInsets.only(left: Dimensions.paddingSize * 0.7),
                  child: Text(
                    selectMethod.value,
                    style: GoogleFonts.inter(
                        fontSize: Dimensions.headingTextSize4,
                        fontWeight: FontWeight.w600,
                        color: CustomColor.primaryLightColor),
                  ),
                ),
                icon: const Padding(
                  padding: EdgeInsets.only(right: 4),
                  child: Icon(
                    Icons.arrow_drop_down,
                    color: CustomColor.primaryTextColor,
                  ),
                ),
                isExpanded: true,
                underline: Container(),
                borderRadius: BorderRadius.circular(Dimensions.radius),
                items:
                    itemsList.map<DropdownMenuItem<ReceiverCountry>>((value) {
                  return DropdownMenuItem<ReceiverCountry>(
                    value: value,
                    child: Text(value.name.toString(),
                        style: CustomStyle.lightHeading3TextStyle),
                  );
                }).toList(),
                onChanged: onChanged,
              ),
            ),
          ),
        ));
  }
}
