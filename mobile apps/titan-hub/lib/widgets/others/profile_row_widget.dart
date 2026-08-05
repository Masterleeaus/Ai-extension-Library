import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/size.dart';
import 'package:qrpay/views/others/custom_image_widget.dart';
import 'package:flutter/material.dart';

import '../text_labels/title_heading3_widget.dart';

class TileWidget extends StatelessWidget {
  const TileWidget(
      {super.key, required this.icon, required this.text, required this.iconColor, required this.textColor});

  final String icon, text;
  final Color iconColor, textColor;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        CustomImageWidget(
          path: icon,
          height: 24,
          width: 24,
          color: iconColor,
        ),
        horizontalSpace(Dimensions.widthSize * 2),
        TitleHeading3Widget(
          text: text,
          fontWeight: FontWeight.w600,
          color: textColor,
        ),
      ],
    );
  }
}
