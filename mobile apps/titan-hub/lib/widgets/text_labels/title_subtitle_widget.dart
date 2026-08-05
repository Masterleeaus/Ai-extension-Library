import 'package:flutter/material.dart';
import 'package:qrpay/utils/custom_style.dart';
import 'package:qrpay/widgets/text_labels/custom_title_heading_widget.dart';

import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import 'title_heading1_widget.dart';

class TitleSubTitleWidget extends StatelessWidget {
  const TitleSubTitleWidget(
      {super.key,
      required this.title,
      required this.subtitle,
      this.crossAxisAlignment = CrossAxisAlignment.start});
  final String title, subtitle;
  final CrossAxisAlignment crossAxisAlignment;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: crossAxisAlignment,
      children: [
        TitleHeading1Widget(
          text: title,
          padding: EdgeInsets.only(bottom: Dimensions.marginSizeVertical * 0.3),
        ),
        CustomTitleHeadingWidget(
          text: subtitle,
          textAlign: TextAlign.center,
          style: CustomStyle.darkHeading4TextStyle.copyWith(
            fontSize: Dimensions.headingTextSize4,
            fontWeight: FontWeight.w500,
            color: CustomColor.primaryLightTextColor.withOpacity(0.5),
          ),
        ),
      ],
    );
  }
}
