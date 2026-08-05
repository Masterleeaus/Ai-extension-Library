import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:qrpay/utils/size.dart';

import '../../language/english.dart';
import '../../utils/custom_color.dart';
import '../../utils/custom_style.dart';
import '../../utils/dimensions.dart';
import '../text_labels/custom_title_heading_widget.dart';
import '../text_labels/title_heading4_widget.dart';
import 'decimal_formater_widget.dart';

class InputWithText extends StatefulWidget {
  final String hint, icon, label, suffixText;
  final int maxLines;
  final bool isValidator;
  final EdgeInsetsGeometry? paddings;
  final TextEditingController controller;
  final Function(String)? onChanged;

  const InputWithText({
    super.key,
    required this.controller,
    required this.hint,
    this.icon = "",
    this.isValidator = true,
    this.maxLines = 1,
    this.paddings,
    this.onChanged,
    required this.label,
    required this.suffixText,
  });

  @override
  State<InputWithText> createState() => _PrimaryInputWidgetState();
}

class _PrimaryInputWidgetState extends State<InputWithText> {
  FocusNode? focusNode;

  @override
  void initState() {
    super.initState();
    focusNode = FocusNode();
  }

  @override
  void dispose() {
    focusNode!.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return Column(
      mainAxisAlignment: MainAxisAlignment.start,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TitleHeading4Widget(
          text: widget.label,
          fontWeight: FontWeight.w600,
        ),
        verticalSpace(Dimensions.heightSize * 0.7),
        Row(
          children: [
            Expanded(
              flex: 9,
              child: TextFormField(
                onChanged: widget.onChanged,
                validator: widget.isValidator == false
                    ? null
                    : (String? value) {
                        if (value!.isEmpty) {
                          return Strings.pleaseFillOutTheField;
                        } else {
                          return null;
                        }
                      },
                textInputAction: TextInputAction.next,
                controller: widget.controller,
                onTap: () {
                  setState(() {
                    focusNode!.requestFocus();
                  });
                },
                onFieldSubmitted: (value) {
                  setState(() {
                    focusNode!.unfocus();
                  });
                },
                focusNode: focusNode,
                textAlign: TextAlign.left,
                style: CustomStyle.darkHeading3TextStyle.copyWith(
                  color: Get.isDarkMode
                      ? CustomColor.primaryDarkTextColor.withOpacity(0.2)
                      : CustomColor.primaryTextColor,
                ),
                inputFormatters: <TextInputFormatter>[
                  DecimalTextInputFormatter(decimalRange: 2)
                ],
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                maxLines: widget.maxLines,
                decoration: InputDecoration(
                  hintText: widget.hint,
                  hintStyle: GoogleFonts.inter(
                    fontSize: Dimensions.headingTextSize3,
                    fontWeight: FontWeight.w500,
                    color: Get.isDarkMode
                        ? CustomColor.primaryDarkTextColor.withOpacity(0.2)
                        : CustomColor.primaryTextColor.withOpacity(0.2),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    borderSide: BorderSide(
                      color: CustomColor.primaryLightColor.withOpacity(0.2),
                    ),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    borderSide: BorderSide(
                        width: 2, color: CustomColor.primaryLightColor),
                  ),
                  contentPadding: EdgeInsets.symmetric(
                    horizontal: Dimensions.widthSize * 1.7,
                    vertical: Dimensions.heightSize,
                  ),
                  suffixIcon: Container(
                    height: Dimensions.inputBoxHeight,
                    alignment: Alignment.center,
                    width: isTablet()
                        ? Dimensions.widthSize * 6
                        : Dimensions.widthSize * 7.5,
                    decoration: BoxDecoration(
                      color: CustomColor.primaryLightColor,
                      borderRadius: BorderRadius.only(
                        topRight: Radius.circular(Dimensions.radius * 0.5),
                        bottomRight: Radius.circular(Dimensions.radius * 0.5),
                      ),
                    ),
                    child: CustomTitleHeadingWidget(
                      text: widget.suffixText,
                      style: CustomStyle.darkHeading4TextStyle.copyWith(
                        fontWeight: FontWeight.w500,
                        fontSize: Dimensions.headingTextSize3,
                        color: CustomColor.whiteColor,
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        )
      ],
    );
  }
}
