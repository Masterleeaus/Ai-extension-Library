import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../language/language_controller.dart';

class PrimaryInputWidget extends StatefulWidget {
  final String hint, icon, label;
  final int maxLines;
  final bool isValidator;
  final bool isTrxHash;
  final EdgeInsetsGeometry? paddings;
  final TextEditingController controller;
  final TextInputType? keyboardType;
  final List<TextInputFormatter>? inputFormatters;
  final ValueChanged? onChanged;
  final ValueChanged? onFieldSubmitted;
  final bool? readOnly;
  final Widget? suffixIcon;
  final Widget? prefixIcon;
  final String? optionalLabel;
  final double? radius;

  const PrimaryInputWidget({
    super.key,
    required this.controller,
    required this.hint,
    this.icon = "",
    this.isValidator = true,
    this.isTrxHash = false,
    this.maxLines = 1,
    this.paddings,
    required this.label,
    this.keyboardType,
    this.inputFormatters,
    this.onChanged,
    this.onFieldSubmitted,
    this.readOnly,
    this.suffixIcon,
    this.prefixIcon,
    this.optionalLabel,
    this.radius,
  });

  @override
  State<PrimaryInputWidget> createState() => _PrimaryInputWidgetState();
}

class _PrimaryInputWidgetState extends State<PrimaryInputWidget> {
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

  final languageController = Get.put(LanguageController());

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.start,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (widget.label != '')
          Row(
            children: [
              TitleHeading4Widget(
                text: widget.label,
                fontWeight:
                    widget.isTrxHash ? FontWeight.w400 : FontWeight.w600,
                color: widget.isTrxHash
                    ? CustomColor.whiteColor.withOpacity(0.5)
                    : null,
              ),
              horizontalSpace(Dimensions.widthSize * 0.5),
              TitleHeading4Widget(
                text: widget.optionalLabel ?? "",
                fontWeight: FontWeight.w600,
                fontSize: Dimensions.headingTextSize5,
                color: CustomColor.primaryLightColor.withOpacity(.8),
              ),
            ],
          ),
        verticalSpace(7),
        Obx(
          () => TextFormField(
            readOnly: widget.readOnly ?? false,
            validator: widget.isValidator == false
                ? null
                : (String? value) {
                    if (value!.isEmpty) {
                      return Strings.pleaseFillOutTheField.translation;
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
            onFieldSubmitted: widget.onFieldSubmitted ??
                (value) {
                  setState(() {
                    focusNode!.unfocus();
                  });
                },
            onChanged: widget.onChanged,
            focusNode: focusNode,
            textAlign: TextAlign.left,
            style: Get.isDarkMode
                ? CustomStyle.darkHeading3TextStyle
                : CustomStyle.lightHeading3TextStyle,
            keyboardType: widget.keyboardType,
            inputFormatters: widget.inputFormatters,
            maxLines: widget.maxLines,
            decoration: InputDecoration(
              hintText: languageController.getTranslation(widget.hint),
              hintStyle: GoogleFonts.inter(
                fontSize: Dimensions.headingTextSize3,
                fontWeight: FontWeight.w500,
                color: widget.isTrxHash
                    ? CustomColor.primaryDarkTextColor.withOpacity(0.2)
                    : Get.isDarkMode
                        ? CustomColor.primaryDarkTextColor.withOpacity(0.2)
                        : CustomColor.primaryLightTextColor.withOpacity(0.2),
              ),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(
                    widget.radius ?? Dimensions.radius * 0.5),
                borderSide: BorderSide(
                  color: widget.isTrxHash
                      ? CustomColor.whiteColor
                      : CustomColor.transparent,
                ),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(
                    widget.radius ?? Dimensions.radius * 0.5),
                borderSide: BorderSide(
                  color: widget.isTrxHash
                      ? CustomColor.whiteColor
                      : CustomColor.primaryLightColor.withOpacity(0.2),
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(
                    widget.radius ?? Dimensions.radius * 0.5),
                borderSide: BorderSide(
                    width: 2,
                    color: widget.isTrxHash
                        ? CustomColor.whiteColor
                        : CustomColor.primaryLightColor),
              ),
              contentPadding: EdgeInsets.symmetric(
                horizontal: Dimensions.widthSize * 1.7,
                vertical: Dimensions.heightSize,
              ),
              suffixIcon: widget.suffixIcon,
              prefixIcon: widget.prefixIcon,
            ),
          ),
        ),
      ],
    );
  }
}

extension SuperLanguage on String {
  get translation => Get.find<LanguageController>().getTranslation(this);
}
