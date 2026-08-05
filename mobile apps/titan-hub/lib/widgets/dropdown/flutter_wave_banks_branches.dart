// import 'package:google_fonts/google_fonts.dart';
// import 'package:qrpay/utils/basic_screen_imports.dart';
// import 'package:qrpay/utils/custom_switch_loading_api.dart';

// import '../../controller/categories/withdraw_controller/withdraw_controller.dart';
// import '../../language/language_controller.dart';

// class FlutterWaveBanksBranchesDropDown extends StatefulWidget {
//   const FlutterWaveBanksBranchesDropDown({super.key});

//   @override
//   State<FlutterWaveBanksBranchesDropDown> createState() =>
//       _FlutterWaveBanksBranchesDropDownState();
// }

// class _FlutterWaveBanksBranchesDropDownState
//     extends State<FlutterWaveBanksBranchesDropDown> {
//   final FocusNode focusNode = FocusNode();
//   final controller = Get.put(WithdrawController());

//   @override
//   void initState() {
//     super.initState();
//   }

//   @override
//   void dispose() {
//     focusNode.dispose();
//     super.dispose();
//   }

//   void _openBankSearch() {
//     showModalBottomSheet(
//       context: context,
//       isScrollControlled: true,
//       backgroundColor: Colors.transparent,
//       builder: (context) => DraggableScrollableSheet(
//         maxChildSize: 1,
//         initialChildSize: 0.8,
//         minChildSize: 0.8,
//         builder: (context, scrollController) => Container(
//           padding: const EdgeInsets.all(16),
//           decoration: BoxDecoration(
//             color: Colors.white,
//             borderRadius: BorderRadius.vertical(
//               top: Radius.circular(Dimensions.radius * 2),
//             ),
//           ),
//           child: Column(
//             crossAxisAlignment: CrossAxisAlignment.start,
//             children: [
//               Row(
//                 children: [
//                   const BackButton(),
//                   horizontalSpace(Dimensions.widthSize * 6),
//                   const TitleHeading3Widget(
//                     text: Strings.selectBranch,
//                   ),
//                 ],
//               ),
//               PrimaryInputWidget(
//                 controller: controller.branchNameSearchController,
//                 hint: Get.find<LanguageController>()
//                     .getTranslation(Strings.search),
//                 label: '',
//                 prefixIcon: const Icon(Icons.search),
//                 onChanged: (value) {
//                   controller.filterBranch(value);
//                 },
//                 radius: Dimensions.radius,
//               ),
//               Expanded(
//                 child: Obx(() {
//                   return controller.branch.value.isEmpty
//                       ? Container(
//                           margin: EdgeInsets.symmetric(
//                             vertical: Dimensions.marginSizeVertical * 0.4,
//                           ),
//                           child: const Center(
//                             child: TitleHeading4Widget(
//                               text: Strings.noBranchFound,
//                             ),
//                           ),
//                         )
//                       : ListView.builder(
//                           controller: scrollController,
//                           itemCount: controller.branch.value.length,
//                           itemBuilder: (_, index) {
//                             var data = controller.branch.value[index];
//                             return ListTile(
//                               title: TitleHeading4Widget(text: data.branchName),
//                               onTap: () {
//                                 controller.selectFlutterWaveBankBranchCode
//                                     .value = data.branchCode;
//                                 controller.selectFlutterWaveBankBranchName
//                                     .value = data.branchName;

//                                 controller.branchNameController.text =
//                                     data.branchName;

//                                 controller.isBranchSearchEnable.value = false;
//                                 Navigator.pop(context);
//                               },
//                             );
//                           },
//                         );
//                 }),
//               ),
//             ],
//           ),
//         ),
//       ),
//     );
//   }

//   @override
//   Widget build(BuildContext context) {
//     return Obx(
//       () => controller.isBranchLoading
//           ? const CustomSwitchLoading()
//           : Column(
//               crossAxisAlignment: CrossAxisAlignment.start,
//               children: [
//                 CustomTitleHeadingWidget(
//                   text: Strings.bankBranch.tr,
//                   style: CustomStyle.darkHeading4TextStyle.copyWith(
//                     fontWeight: FontWeight.w600,
//                     color: CustomColor.primaryTextColor,
//                   ),
//                 ),
//                 const SizedBox(height: 7),
//                 TextFormField(
//                   controller: controller.branchNameController,
//                   readOnly: true,
//                   onTap: _openBankSearch,
//                   decoration: InputDecoration(
//                     hintText: Get.find<LanguageController>()
//                         .getTranslation(Strings.enterBranchName),
//                     hintStyle: GoogleFonts.inter(
//                       fontSize: Dimensions.headingTextSize3,
//                       fontWeight: FontWeight.w500,
//                       color: CustomColor.primaryTextColor.withOpacity(0.2),
//                     ),
//                     border: OutlineInputBorder(
//                       borderRadius:
//                           BorderRadius.circular(Dimensions.radius * 0.5),
//                     ),
//                     enabledBorder: OutlineInputBorder(
//                       borderRadius:
//                           BorderRadius.circular(Dimensions.radius * 0.5),
//                       borderSide: BorderSide(
//                         color: CustomColor.primaryLightColor.withOpacity(0.2),
//                       ),
//                     ),
//                     focusedBorder: OutlineInputBorder(
//                       borderRadius:
//                           BorderRadius.circular(Dimensions.radius * 0.5),
//                       borderSide: BorderSide(
//                           width: 2, color: CustomColor.primaryLightColor),
//                     ),
//                     contentPadding: EdgeInsets.symmetric(
//                       horizontal: Dimensions.widthSize * 1.7,
//                       vertical: Dimensions.heightSize,
//                     ),
//                     suffixIcon: Icon(
//                       Icons.arrow_drop_down,
//                       size: Dimensions.iconSizeLarge,
//                       color: CustomColor.primaryLightColor,
//                     ),
//                   ),
//                 ),
//                 verticalSpace(Dimensions.heightSize)
//               ],
//             ),
//     );
//   }
// }
