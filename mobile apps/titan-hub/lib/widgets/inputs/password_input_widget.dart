import 'package:google_fonts/google_fonts.dart';
import 'package:qrpay/language/language_controller.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';

class PasswordInputWidget extends StatefulWidget {
  final String hint, icon, label;
  final int maxLines;
  final bool isValidator;
  final EdgeInsetsGeometry? paddings;
  final TextEditingController controller;

  const PasswordInputWidget({
    super.key,
    required this.controller,
    required this.hint,
    this.icon = "",
    this.isValidator = true,
    this.maxLines = 1,
    this.paddings,
    required this.label,
  });

  @override
  State<PasswordInputWidget> createState() => _PrimaryInputWidgetState();
}

class _PrimaryInputWidgetState extends State<PasswordInputWidget> {
  FocusNode? focusNode;
  bool obscureText = true;

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
        TitleHeading4Widget(
          text: widget.label,
          fontWeight: FontWeight.w600,
        ),
        verticalSpace(7),
        Obx(
          () => TextFormField(
            validator: widget.isValidator == false
                ? null
                : (String? value) {
                    if (value!.isEmpty) {
                      return languageController
                          .getTranslation(Strings.pleaseFillOutTheField);
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
            obscureText: obscureText,
            focusNode: focusNode,
            textAlign: TextAlign.left,
            style: Get.isDarkMode
                ? CustomStyle.darkHeading3TextStyle
                : CustomStyle.lightHeading3TextStyle,
            maxLines: widget.maxLines,
            decoration: InputDecoration(
              hintText: languageController.getTranslation(widget.hint),
              hintStyle: GoogleFonts.inter(
                fontSize: Dimensions.headingTextSize3,
                fontWeight: FontWeight.w500,
                color: Get.isDarkMode
                    ? CustomColor.primaryDarkTextColor.withOpacity(0.2)
                    : CustomColor.primaryLightTextColor.withOpacity(0.2),
              ),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(Dimensions.radius * 0.7),
                borderSide:
                    const BorderSide(width: 2, color: CustomColor.whiteColor),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
                borderSide: BorderSide(
                  color: CustomColor.primaryLightColor.withOpacity(0.2),
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
                borderSide:
                    BorderSide(width: 2, color: CustomColor.primaryLightColor),
              ),
              contentPadding: EdgeInsets.symmetric(
                horizontal: Dimensions.widthSize * 1.7,
                vertical: Dimensions.heightSize,
              ),
              suffixIcon: GestureDetector(
                onTap: () {
                  setState(() {
                    obscureText = !obscureText;
                  });
                },
                child: Icon(
                  obscureText ? Icons.visibility_off : Icons.visibility,
                  color: focusNode!.hasFocus
                      ? CustomColor.primaryDarkColor
                      : Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.2)
                          : CustomColor.primaryLightTextColor.withOpacity(0.2),
                ),
              ),
            ),
          ),
        )
      ],
    );
  }
}
